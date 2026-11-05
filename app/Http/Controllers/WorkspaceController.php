<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends Controller
{
    public function select(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);
        $request->session()->put('organization_id', $organization->id);

        return redirect('/organization');
    }

    public function save(Request $request, Organization $organization): JsonResponse
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);
        abort_unless($organization->hasAccess() || $request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['data' => 'required|array', 'version' => 'required|integer|min:0']);

        return DB::transaction(function () use ($organization, $data) {
            $organization = Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
            abort_unless($organization->workspace_version === $data['version'], 409, 'Another member changed this workspace. Reload before editing.');
            $organization->update(['workspace_data' => json_encode($data['data']), 'workspace_version' => $data['version'] + 1]);

            return response()->json(['version' => $data['version'] + 1]);
        });
    }
}
