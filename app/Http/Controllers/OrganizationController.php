<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function destroy(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()), 403);

        if ($organization->checkout_expires_at?->isFuture()) {
            return back()->withErrors(['organization' => 'An open Stripe checkout must expire before this organization can be deleted.']);
        }

        if ($organization->stripe_checkout_id && ! $organization->stripe_subscription_id) {
            if (! config('accounts.stripe_secret')) {
                return back()->withErrors(['organization' => 'Billing cannot be checked right now. Please contact the administrator.']);
            }
            try {
                $checkout = Http::withToken(config('accounts.stripe_secret'))->timeout(15)
                    ->get('https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($organization->stripe_checkout_id))->throw()->json();
            } catch (\Throwable $exception) {
                report($exception);

                return back()->withErrors(['organization' => 'Billing could not be checked. Please try again.']);
            }
            if (($checkout['status'] ?? null) !== 'expired' || ! empty($checkout['subscription'])) {
                return back()->withErrors(['organization' => 'The Stripe checkout is still being processed. Please wait for billing to update before deleting this organization.']);
            }
        }

        if ($organization->stripe_subscription_id) {
            if (! config('accounts.stripe_secret')) {
                return back()->withErrors(['organization' => 'Billing cannot be checked right now. Please contact the administrator.']);
            }
            try {
                $subscription = Http::withToken(config('accounts.stripe_secret'))->timeout(15)
                    ->get('https://api.stripe.com/v1/subscriptions/'.rawurlencode($organization->stripe_subscription_id))->throw()->json();
            } catch (\Throwable $exception) {
                report($exception);

                return back()->withErrors(['organization' => 'Billing could not be checked. Please try again.']);
            }
            if (! in_array($subscription['status'] ?? null, ['canceled', 'incomplete_expired'], true)) {
                return back()->withErrors(['organization' => 'Cancel the Stripe subscription in Manage billing before deleting this organization.']);
            }
        }

        $organization->delete();
        if ((string) $request->session()->get('organization_id') === (string) $organization->id) {
            $request->session()->forget('organization_id');
        }

        return redirect()->route('account')->with('status', 'Organization deleted.');
    }

    public function suggestions(Request $request, Organization $organization): JsonResponse
    {
        abort_unless($organization->canManage($request->user()) && ($organization->hasAccess() || $request->user()->isSuperAdmin()), 403);
        $data = $request->validate(['q' => 'required|string|min:2|max:100']);
        $query = str_replace(['%', '_'], ['\\%', '\\_'], $data['q']);

        return response()->json(User::where(function ($builder) use ($query) {
            $builder->where('name', 'like', '%'.$query.'%')->orWhere('email', 'like', '%'.$query.'%')->orWhere('mykkp_id', 'like', '%'.$query.'%');
        })->limit(8)->get(['id', 'name', 'email', 'mykkp_id']));
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()), 403);
        $data = $request->validate(['name' => 'required|string|max:160', 'address' => 'nullable|string|max:255', 'registration' => 'nullable|string|max:255', 'industry' => 'nullable|string|max:255', 'site' => 'nullable|string|max:255', 'employees' => 'nullable|integer|min:0', 'phone' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255']);
        if ($request->has('workplace_settings')) {
            $settings = $request->validate(['lead' => 'required|in:OSH Coordinator,SHO,Management / no dedicated OSH staff', 'foreign' => 'required|in:yes,no', 'exposures' => 'sometimes|array', 'exposures.*' => 'in:chemical,noise,ergo,iaq,machinery,contractor,hotwork,height,confined']);
            DB::transaction(function () use ($organization, $data, $settings) {
                $locked = Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
                $workspace = json_decode($locked->workspace_data ?? '{}', true) ?: [];
                $profile = array_merge($workspace['profile'] ?? [], $data, ['company' => $data['name'], 'lead' => $settings['lead'], 'foreign' => $settings['foreign']]);
                foreach (['chemical', 'noise', 'ergo', 'iaq', 'machinery', 'contractor', 'hotwork', 'height', 'confined'] as $key) {
                    $profile[$key] = in_array($key, $settings['exposures'] ?? [], true);
                }
                $workspace['profile'] = $profile;
                $locked->update(array_merge($data, ['workspace_data' => json_encode($workspace), 'workspace_version' => $locked->workspace_version + 1]));
            });
        } else {
            $organization->update($data);
        }

        return back()->with('status', 'Organization details updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->fresh()->can_create_organization || $request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:160']);
        $organization = DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail()->assertAvailableForOrganization();
            $organization = Organization::create(['name' => $data['name'], 'owner_id' => $request->user()->id]);
            $organization->users()->attach($request->user()->id);

            return $organization;
        });

        return redirect()->route('organizations.show', $organization)->with('status', 'Organization created. Activate your subscription to add members.');
    }

    public function show(Request $request, Organization $organization): View
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);

        return view('accounts.organization', ['organization' => $organization->load('users', 'owner')]);
    }

    public function addMember(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()), 403);
        abort_unless($organization->hasAccess(), 403, 'An active subscription is required.');
        $data = $request->validate(['email' => 'required|email']);
        $user = User::where('email', strtolower($data['email']))->first();
        if (! $user) {
            return back()->withErrors(['email' => 'Ask this person to create a free profile first, then add their email here.']);
        }
        DB::transaction(function () use ($organization, $user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail()->assertAvailableForOrganization($organization->id);
            $organization->users()->syncWithoutDetaching([$user->id]);
        });

        return back()->with('status', 'Member added.');
    }

    public function updateMemberRole(Request $request, Organization $organization, User $user): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()), 403);
        abort_if($organization->owner_id === $user->id && $request->has('role'), 422);
        abort_unless($organization->users()->whereKey($user->id)->exists(), 404);
        $data = $request->validate(['role' => 'sometimes|required|in:viewer,editor', 'committee_role' => 'nullable|in:Chairperson,Secretary,Employer representative,Employee representative']);
        $organization->users()->updateExistingPivot($user->id, $data);

        return back()->with('status', 'Member role updated.');
    }

    public function removeMember(Request $request, Organization $organization, User $user): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()), 403);
        abort_if($organization->owner_id === $user->id, 422, 'The owner cannot be removed.');
        $organization->users()->detach($user->id);

        return back()->with('status', 'Member removed.');
    }
}
