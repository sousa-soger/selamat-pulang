<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BillingController extends Controller
{
    private function stripe(): PendingRequest
    {
        return Http::withToken(config('accounts.stripe_secret'))->asForm()->timeout(20);
    }

    public function checkout(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()), 403);
        if (! config('accounts.stripe_secret') || ! config('accounts.stripe_price_id')) {
            return back()->withErrors(['billing' => 'Organization subscriptions are not configured yet. Please contact the administrator.']);
        }
        if ($organization->hasAccess() || ($organization->stripe_subscription_id && ! in_array($organization->subscription_status, ['canceled', 'incomplete_expired']))) {
            return back()->withErrors(['billing' => 'Use Manage billing to update your existing subscription.']);
        }
        try {
            $url = DB::transaction(function () use ($organization) {
                $organization = Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
                if ($organization->checkout_expires_at?->isFuture() && $organization->stripe_checkout_url) {
                    return $organization->stripe_checkout_url;
                }
                $parameters = [
                    'mode' => 'subscription', 'line_items' => [['price' => config('accounts.stripe_price_id'), 'quantity' => 1]],
                    'client_reference_id' => (string) $organization->id,
                    'subscription_data' => ['metadata' => ['organization_id' => (string) $organization->id]],
                    'success_url' => route('organizations.show', $organization).'?checkout=success',
                    'cancel_url' => route('organizations.show', $organization).'?checkout=cancelled',
                ];
                if ($organization->stripe_customer_id) {
                    $parameters['customer'] = $organization->stripe_customer_id;
                } else {
                    $parameters['customer_email'] = $organization->owner->email;
                }
                $session = $this->stripe()->withHeaders(['Idempotency-Key' => 'organization-'.$organization->id.'-'.($organization->stripe_checkout_id ?? 'initial')])
                    ->post('https://api.stripe.com/v1/checkout/sessions', $parameters)->throw()->json();
                $organization->update(['stripe_checkout_id' => $session['id'], 'stripe_checkout_url' => $session['url'], 'checkout_expires_at' => Carbon::createFromTimestamp($session['expires_at'])]);

                return $session['url'];
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['billing' => 'Stripe is temporarily unavailable. Please try again.']);
        }

        return redirect()->away($url);
    }

    public function portal(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()), 403);
        abort_unless($organization->stripe_customer_id && config('accounts.stripe_secret'), 422);
        try {
            $session = $this->stripe()->post('https://api.stripe.com/v1/billing_portal/sessions', [
                'customer' => $organization->stripe_customer_id, 'return_url' => route('organizations.show', $organization),
            ])->throw()->json();

            return redirect()->away($session['url']);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['billing' => 'Billing is temporarily unavailable. Please try again.']);
        }
    }

    public function webhook(Request $request): Response
    {
        $secret = config('accounts.stripe_webhook_secret');
        abort_unless($secret, 503);
        $parts = explode(',', (string) $request->header('Stripe-Signature'));
        $timestamp = null;
        $signatures = [];
        foreach ($parts as $part) {
            [$key,$value] = array_pad(explode('=', $part, 2), 2, '');
            if ($key === 't') {
                $timestamp = $value;
            } if ($key === 'v1') {
                $signatures[] = $value;
            }
        }
        abort_unless(ctype_digit((string) $timestamp) && abs(time() - (int) $timestamp) <= 300, 400);
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(collect($signatures)->contains(fn (string $signature): bool => hash_equals($expected, $signature)), 400);
        $event = json_decode($request->getContent(), true);
        abort_unless(is_array($event) && isset($event['type'],$event['data']['object']), 400);
        $object = $event['data']['object'];
        $subscriptionId = null;
        if ($event['type'] === 'checkout.session.completed') {
            $subscriptionId = $object['subscription'] ?? null;
        } elseif (str_starts_with($event['type'], 'customer.subscription.')) {
            $subscriptionId = $object['id'] ?? null;
        }
        if (! $subscriptionId) {
            return response('Ignored');
        }
        // Fetch current state so delayed or repeated webhook deliveries cannot restore stale access.
        $subscription = $this->stripe()->get('https://api.stripe.com/v1/subscriptions/'.rawurlencode($subscriptionId))->throw()->json();
        $organizationId = $subscription['metadata']['organization_id'] ?? null;
        if (! $organizationId) {
            return response('Ignored');
        }
        DB::transaction(function () use ($organizationId, $subscription, $subscriptionId) {
            $organization = Organization::whereKey($organizationId)->lockForUpdate()->first();
            if (! $organization) {
                return;
            }
            if ($organization->stripe_subscription_id && $organization->stripe_subscription_id !== $subscriptionId && ! in_array($organization->subscription_status, ['canceled', 'incomplete_expired'])) {
                return;
            }
            $organization->update(['stripe_subscription_id' => $subscriptionId, 'stripe_customer_id' => $subscription['customer'], 'subscription_status' => $subscription['status'], 'stripe_checkout_url' => null, 'checkout_expires_at' => null]);
        });

        return response('Received');
    }
}
