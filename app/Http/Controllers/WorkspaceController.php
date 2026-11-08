<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function select(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);
        $request->session()->put('organization_id', $organization->id);

        return redirect('/organization');
    }

    public function show(Request $request, Organization $organization): JsonResponse
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);

        return response()->json(['data' => json_decode($organization->workspace_data ?? '{}'), 'version' => $organization->workspace_version]);
    }

    public function save(Request $request, Organization $organization): JsonResponse
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);
        abort_unless($organization->canEdit($request->user()), 403);
        abort_unless($organization->hasAccess() || $request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'data' => 'required|array', 'version' => 'required|integer|min:0',
            'data.hraRecords' => 'sometimes|array|max:200',
            'data.hraRecords.*.id' => 'required|string|max:100|distinct',
            'data.hraRecords.*.title' => 'required|string|max:250',
            'data.hraRecords.*.type' => 'required|in:noise,chemical,ergo,iaq,prisma,other',
            'data.hraRecords.*.owner' => 'nullable|string|max:250',
            'data.hraRecords.*.overview' => 'nullable|string|max:12000',
            'data.hraRecords.*.assessedOn' => 'nullable|date_format:Y-m-d',
            'data.hraRecords.*.due' => 'nullable|date_format:Y-m-d',
            'data.hraRecords.*.priority' => 'required|in:Unassigned,Low,Medium,High,Urgent',
            'data.hraRecords.*.status' => 'required|in:Draft,In progress,Reviewed',
            'data.hraRecords.*.items' => 'present|array|max:100',
            'data.hraRecords.*.items.*.title' => 'required|string|max:250',
            'data.hraRecords.*.items.*.description' => 'nullable|string|max:12000',
            'data.hraRecords.*.items.*.priority' => 'required|in:Unassigned,Low,Medium,High,Urgent',
            'data.hraRecords.*.items.*.status' => 'required|in:Open,In progress,Done',
            'data.hraRecords.*.items.*.due' => 'nullable|date_format:Y-m-d',
            'data.hraRecords.*.items.*.steps' => 'present|array|max:100',
            'data.hraRecords.*.items.*.steps.*.title' => 'required|string|max:250',
            'data.hraRecords.*.items.*.steps.*.description' => 'nullable|string|max:12000',
            'data.hraRecords.*.documents' => 'present|array|max:30',
            'data.hraRecords.*.documents.*.id' => 'required|uuid',
            'data.hraRecords.*.documents.*.name' => 'required|string|max:200',
        ]);
        $data = $request->only(['data', 'version']);

        return DB::transaction(function () use ($organization, $data) {
            $organization = Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
            abort_unless($organization->workspace_version === $data['version'], 409, 'Another member changed this workspace. Reload before editing.');
            $previous = json_decode($organization->workspace_data ?? '{}', true) ?: [];
            $existingRows = collect($previous['hirarc'] ?? [])->pluck('id')->all();
            $statements = collect($previous['jms'] ?? []);
            foreach ($data['data']['hirarc'] ?? [] as $row) {
                if (in_array($row['id'] ?? null, $existingRows, true)) {
                    continue;
                }
                $statement = $statements->firstWhere('id', $row['jmsId'] ?? null);
                if (! $statement || ! in_array($row['step'] ?? null, $statement['steps'] ?? [], true)) {
                    throw ValidationException::withMessages(['data.hirarc' => 'Each new HIRARC row must reference a saved JMS job step.']);
                }
            }
            foreach ($data['data']['permits'] ?? [] as $permit) {
                $oldPermit = collect($previous['permits'] ?? [])->firstWhere('id', $permit['id'] ?? null);
                if (! $oldPermit || (($oldPermit['status'] ?? '') !== 'Active' && ($permit['status'] ?? '') === 'Active')) {
                    $memberId = $permit['memberId'] ?? null;
                    $validMember = $memberId && ($organization->owner_id == $memberId || $organization->users()->whereKey($memberId)->exists());
                    $validRisk = collect($data['data']['hirarc'] ?? [])->contains('id', $permit['hir'] ?? null);
                    $expiry = strtotime($permit['until'] ?? '');
                    if (! $validMember || ! $validRisk || ! filled($permit['controls'] ?? '') || ! $expiry || $expiry <= time()) {
                        throw ValidationException::withMessages(['data.permits' => 'A new or issued permit requires an organization member, saved HIRARC assessment, controls and a future expiry.']);
                    }
                }
            }
            $organization->update(['workspace_data' => json_encode($data['data']), 'workspace_version' => $data['version'] + 1]);

            return response()->json(['version' => $data['version'] + 1]);
        });
    }
}
