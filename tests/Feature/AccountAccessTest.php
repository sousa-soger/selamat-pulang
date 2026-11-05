<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::factory()->create(['email' => 'sogerft@gmail.com', 'role' => 'super_admin']);
    }

    public function test_guests_can_view_auth_pages_but_not_workspace(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/')->assertRedirect('/login');
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_registration_is_free_and_cannot_grant_admin_role(): void
    {
        $this->post('/register', ['name' => 'Owner', 'email' => 'SOGERFT@gmail.com', 'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123', 'role' => 'super_admin'])->assertRedirect('/account');
        $user = User::first();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->isSuperAdmin());
        $this->assertSame('user', $user->role);
        $this->assertDatabaseCount('organizations', 0);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_designated_email_becomes_super_admin(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'sogerft@gmail.com']);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertRedirect('/account');
        $this->assertTrue($user->fresh()->isSuperAdmin());
    }

    public function test_login_logout_and_rate_limit(): void
    {
        $user = User::factory()->create(['password' => 'safe-password-123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'safe-password-123'])->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_google_rejects_invalid_state_without_network_calls(): void
    {
        $this->withSession(['google_state' => 'expected'])->get('/auth/google/callback?state=wrong&code=code')->assertRedirect('/login')->assertSessionHasErrors('google');
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_google_verified_identity_can_create_designated_admin(): void
    {
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-token']), 'openidconnect.googleapis.com/*' => Http::response(['sub' => 'google-id', 'email' => 'sogerft@gmail.com', 'email_verified' => true, 'name' => 'Admin'])]);
        $this->withSession(['google_state' => 'expected'])->get('/auth/google/callback?state=expected&code=code')->assertRedirect('/account');
        $this->assertTrue(User::first()->isSuperAdmin());
        $this->assertAuthenticated();
    }

    public function test_google_does_not_link_unverified_existing_password_account(): void
    {
        $user = User::factory()->unverified()->create();
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-token']), 'openidconnect.googleapis.com/*' => Http::response(['sub' => 'google-id', 'email' => $user->email, 'email_verified' => true])]);
        $this->withSession(['google_state' => 'expected'])->get('/auth/google/callback?state=expected&code=code')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_private_certificate_upload_review_and_resubmission(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post('/account/certificate', ['mykkp_id' => 'KKP-123', 'certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf')])->assertSessionHasNoErrors();
        $user->refresh();
        $first = $user->certificate_path;
        $this->assertSame('pending', $user->certificate_status);
        Storage::disk('local')->assertExists($first);
        $this->actingAs(User::factory()->create())->get('/certificates/'.$user->id)->assertForbidden();
        $this->actingAs($this->admin())->get('/certificates/'.$user->id)->assertOk();
        $this->post('/admin/certificates/'.$user->id, ['decision' => 'approved', 'certificate_path' => $first])->assertSessionHasNoErrors();
        $this->assertSame('approved', $user->fresh()->certificate_status);
        $this->actingAs($user)->post('/account/certificate', ['mykkp_id' => 'KKP-456', 'certificate' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertSame('pending', $user->fresh()->certificate_status);
        Storage::disk('local')->assertMissing($first);
        $this->actingAs(User::where('role', 'super_admin')->first())->post('/admin/certificates/'.$user->id, ['decision' => 'approved', 'certificate_path' => $first])->assertStatus(409);
    }

    public function test_invalid_upload_and_unverified_upload_are_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post('/account/certificate', ['mykkp_id' => '123', 'certificate' => UploadedFile::fake()->create('script.html', 2, 'text/html')])->assertSessionHasErrors('certificate');
        $this->actingAs(User::factory()->unverified()->create())->post('/account/certificate')->assertRedirect('/email/verify');
    }

    public function test_regular_users_cannot_access_admin_actions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/admin')->assertForbidden();
        $this->post('/admin/users')->assertForbidden();
        $this->post('/admin/organizations')->assertForbidden();
        $this->post('/admin/certificates/'.$user->id)->assertForbidden();
    }

    public function test_owner_can_add_multiple_members_only_after_activation(): void
    {
        $owner = User::factory()->create();
        $members = User::factory()->count(2)->create();
        $this->actingAs($owner)->post('/organizations', ['name' => 'Safety Team', 'admin_provisioned' => true])->assertRedirect();
        $organization = Organization::first();
        $this->assertFalse($organization->hasAccess());
        $this->post('/organizations/'.$organization->id.'/members', ['email' => $members[0]->email])->assertForbidden();
        $organization->update(['subscription_status' => 'active']);
        foreach ($members as $member) {
            $this->post('/organizations/'.$organization->id.'/members', ['email' => $member->email])->assertSessionHasNoErrors();
        }
        $this->assertSame(3, $organization->users()->count());
        $this->actingAs($members[0])->get('/organizations/'.$organization->id)->assertOk();
        $this->post('/organizations/'.$organization->id.'/members', ['email' => $members[1]->email])->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/organizations/'.$organization->id)->assertForbidden();
        $this->actingAs($owner)->delete('/organizations/'.$organization->id.'/members/'.$owner->id)->assertStatus(422);
        $this->delete('/organizations/'.$organization->id.'/members/'.$members[0]->id)->assertRedirect();
        $this->assertSame(2, $organization->users()->count());
    }

    public function test_admin_can_create_users_and_provision_organizations(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', ['name' => 'New Owner', 'email' => 'owner@example.com'])->assertSessionHasNoErrors();
        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        Notification::assertSentTo($owner, ResetPassword::class);
        $this->post('/admin/organizations', ['name' => 'Provisioned Team', 'owner_email' => $owner->email, 'admin_provisioned' => 1])->assertRedirect();
        $this->assertTrue(Organization::first()->hasAccess());
        $this->assertSame($owner->id, Organization::first()->owner_id);
        $this->get('/admin')->assertOk()->assertSee('Provisioned Team');
    }

    public function test_checkout_uses_configured_price_and_reuses_open_session(): void
    {
        config(['accounts.stripe_secret' => 'sk_test', 'accounts.stripe_price_id' => 'price_configured']);
        $owner = User::factory()->create();
        $organization = Organization::factory()->create(['owner_id' => $owner->id]);
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/test', 'expires_at' => time() + 3600])]);
        $this->actingAs($owner)->post('/organizations/'.$organization->id.'/checkout')->assertRedirect('https://checkout.stripe.com/test');
        $this->post('/organizations/'.$organization->id.'/checkout')->assertRedirect('https://checkout.stripe.com/test');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['line_items'][0]['price'] === 'price_configured' && $request['mode'] === 'subscription');
        $this->assertFalse($organization->fresh()->hasAccess());
    }

    public function test_billing_configuration_failure_is_actionable(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create(['owner_id' => $owner->id]);
        $this->actingAs($owner)->from('/organizations/'.$organization->id)->post('/organizations/'.$organization->id.'/checkout')->assertSessionHasErrors('billing');
        Http::assertNothingSent();
    }

    public function test_webhook_requires_valid_signature_and_updates_current_subscription_state(): void
    {
        config(['accounts.stripe_webhook_secret' => 'whsec_test', 'accounts.stripe_secret' => 'sk_test']);
        $organization = Organization::factory()->create();
        $event = ['type' => 'customer.subscription.updated', 'data' => ['object' => ['id' => 'sub_1']]];
        $body = json_encode($event);
        $timestamp = time();
        $this->call('POST', '/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        Http::fake(['api.stripe.com/*' => Http::sequence()->push(['id' => 'sub_1', 'customer' => 'cus_1', 'status' => 'active', 'metadata' => ['organization_id' => $organization->id]])->push(['id' => 'sub_1', 'customer' => 'cus_1', 'status' => 'canceled', 'metadata' => ['organization_id' => $organization->id]])]);
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test');
        $this->call('POST', '/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertTrue($organization->fresh()->hasAccess());
        $this->call('POST', '/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertFalse($organization->fresh()->hasAccess());
    }

    public function test_account_and_organization_pages_render(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['owner_id' => $user->id]);
        $organization->users()->attach($user);
        $this->actingAs($user)->get('/account')->assertOk()->assertSee('MyKKP verification');
        $this->get('/organizations/'.$organization->id)->assertOk()->assertSee($organization->name);
    }

    public function test_organization_workspace_is_empty_isolated_and_protected_from_stale_writes(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create(['owner_id' => $owner->id, 'admin_provisioned' => true]);
        $this->assertNull($organization->workspace_data);
        $this->actingAs($owner)->post('/organizations/'.$organization->id.'/select')->assertRedirect('/organization');
        $this->putJson('/organizations/'.$organization->id.'/workspace', ['data' => ['actions' => [['title' => 'Real action']]], 'version' => 0])->assertOk()->assertJson(['version' => 1]);
        $this->assertSame('Real action', json_decode($organization->fresh()->workspace_data, true)['actions'][0]['title']);
        $this->putJson('/organizations/'.$organization->id.'/workspace', ['data' => ['actions' => []], 'version' => 0])->assertStatus(409);
        $this->actingAs(User::factory()->create())->putJson('/organizations/'.$organization->id.'/workspace', ['data' => ['actions' => []], 'version' => 1])->assertForbidden();
        $this->post('/organizations/'.$organization->id.'/select')->assertForbidden();
    }

    public function test_owner_searches_members_and_developer_verifies_organization(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['name' => 'Search Person', 'mykkp_id' => 'MYKKP-998']);
        $organization = Organization::factory()->create(['owner_id' => $owner->id, 'admin_provisioned' => true]);
        $this->actingAs($owner)->getJson('/organizations/'.$organization->id.'/suggestions?q=MYKKP-998')->assertOk()->assertJsonFragment(['email' => $member->email]);
        $this->actingAs($member)->getJson('/organizations/'.$organization->id.'/suggestions?q=Search')->assertForbidden();
        $this->actingAs($this->admin())->patch('/admin/organizations/'.$organization->id, ['name' => 'Verified organization', 'owner_id' => $owner->id, 'verification_status' => 'verified', 'admin_provisioned' => 1])->assertRedirect();
        $this->assertSame('verified', $organization->fresh()->verification_status);
    }

    public function test_super_admin_can_verify_users_but_regular_users_cannot(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs(User::factory()->create())->post('/admin/users/'.$user->id.'/verify')->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->actingAs($this->admin())->post('/admin/users/'.$user->id.'/verify')->assertRedirect();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_admin_and_account_pages_share_workspace_navigation(): void
    {
        $admin = $this->admin();
        foreach (['/', '/account', '/admin'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk()->assertSee('Special Access')->assertSee('Compliance calendar')->assertSee('Workplace profile')->assertSee('id="sidebar"', false);
        }
        $this->actingAs(User::factory()->create())->get('/account')->assertOk()->assertDontSee('Special Access');
    }
}
