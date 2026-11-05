<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function verifyUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }
        $user->promoteDesignatedAdmin();

        return back()->with('status', 'User verified.');
    }

    public function setOrganizationPermission(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['can_create_organization' => 'required|boolean']);
        $user->forceFill($data)->save();

        return back()->with('status', 'Organization creation permission updated.');
    }

    public function deleteUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_if($user->id === $request->user()->id || $user->isSuperAdmin(), 403);
        if (Organization::where('owner_id', $user->id)->exists()) {
            return back()->withErrors(['user' => 'Transfer ownership of this user’s organizations before deleting the account.']);
        }
        $user->delete();

        return back()->with('status', 'User deleted.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)], 'mykkp_id' => 'nullable|string|max:100']);
        if (strtolower($data['email']) !== strtolower($user->email)) {
            $user->email_verified_at = null;
        }
        $user->forceFill($data)->save();

        return back()->with('status', 'User updated.');
    }

    public function updateOrganization(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:160', 'owner_id' => 'required|exists:users,id', 'verification_status' => 'required|in:pending,verified,rejected', 'admin_provisioned' => 'required|boolean']);
        DB::transaction(function () use ($organization, $data) {
            $organization->update($data);
            $organization->users()->syncWithoutDetaching([$data['owner_id']]);
        });

        return back()->with('status', 'Organization updated.');
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        return view('accounts.admin', [
            'pending' => User::where('certificate_status', 'pending')->oldest('certificate_submitted_at')->paginate(15, ['*'], 'certificates'),
            'users' => User::latest()->paginate(15, ['*'], 'users'),
            'organizations' => Organization::with('owner')->latest()->paginate(15, ['*'], 'organizations'),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $request->merge(['email' => strtolower((string) $request->email)]);
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:255|unique:users']);
        $user = User::create($data + ['password' => Str::random(64)]);
        Password::sendResetLink(['email' => $user->email]);
        event(new Registered($user));

        return back()->with('status', 'Profile created. Password setup and verification links sent.');
    }

    public function storeOrganization(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:160', 'owner_email' => 'required|email', 'admin_provisioned' => 'sometimes|boolean']);
        $owner = User::where('email', strtolower($data['owner_email']))->first();
        if (! $owner) {
            return back()->withErrors(['owner_email' => 'Create the owner’s profile first.']);
        }
        $organization = DB::transaction(function () use ($data, $owner, $request) {
            $organization = Organization::create(['name' => $data['name'], 'owner_id' => $owner->id, 'admin_provisioned' => $request->boolean('admin_provisioned')]);
            $organization->users()->attach($owner->id);

            return $organization;
        });

        return redirect()->route('organizations.show', $organization)->with('status', 'Organization registered for its owner.');
    }

    public function review(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'feedback' => 'nullable|required_if:decision,rejected|string|max:2000', 'certificate_path' => 'required|string']);
        $updated = User::whereKey($user->id)->where('certificate_status', 'pending')->where('certificate_path', $data['certificate_path'])
            ->update(['certificate_status' => $data['decision'], 'certificate_feedback' => $data['feedback'] ?? null, 'certificate_reviewed_by' => $request->user()->id, 'certificate_reviewed_at' => now()]);
        abort_unless($updated, 409, 'This submission has changed or was already reviewed. Refresh before reviewing.');

        return back()->with('status', 'Certificate '.$data['decision'].'.');
    }
}
