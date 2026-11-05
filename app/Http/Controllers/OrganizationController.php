<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrganizationController extends Controller
{
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
        $organization->update($data);

        return back()->with('status', 'Organization details updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->fresh()->can_create_organization || $request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:160']);
        $organization = DB::transaction(function () use ($request, $data) {
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
        $organization->users()->syncWithoutDetaching([$user->id]);

        return back()->with('status', 'Member added.');
    }

    public function removeMember(Request $request, Organization $organization, User $user): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()), 403);
        abort_if($organization->owner_id === $user->id, 422, 'The owner cannot be removed.');
        $organization->users()->detach($user->id);

        return back()->with('status', 'Member removed.');
    }
}
