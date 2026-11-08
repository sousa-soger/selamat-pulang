<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkerPortalController extends Controller
{
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);
        $data = $request->validate(['action' => 'required|in:publish,report,ack,review', 'message' => 'required_if:action,publish,report|nullable|string|max:5000', 'language' => 'required_if:action,publish|nullable|in:en,ms,id,bn,ne,my,fil', 'anonymous' => 'nullable|boolean', 'type' => 'required_if:action,report|nullable|in:Hazard,Near miss', 'location' => 'nullable|string|max:255', 'id' => 'required_if:action,ack,review|nullable|uuid', 'status' => 'required_if:action,review|nullable|in:New,In review,Resolved', 'response' => 'nullable|string|max:5000']);
        if (in_array($data['action'], ['publish', 'review'])) {
            abort_unless($organization->canEdit($request->user()), 403);
        }
        DB::transaction(function () use ($organization, $request, $data) {
            $org = Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
            $portal = json_decode($org->portal_data ?? '{}', true) ?: ['messages' => [], 'reports' => []];
            $now = now()->toIso8601String();
            if ($data['action'] === 'publish') {
                array_unshift($portal['messages'], ['id' => (string) Str::uuid(), 'message' => $data['message'], 'language' => $data['language'], 'author' => $request->user()->name, 'at' => $now, 'acks' => []]);
            }
            if ($data['action'] === 'report') {
                array_unshift($portal['reports'], ['id' => (string) Str::uuid(), 'message' => $data['message'], 'type' => $data['type'], 'location' => $data['location'] ?? '', 'author' => ! empty($data['anonymous']) ? 'Anonymous' : $request->user()->name, 'at' => $now, 'status' => 'New', 'responses' => []]);
            }
            if ($data['action'] === 'ack') {
                $index = array_search($data['id'], array_column($portal['messages'], 'id'), true);
                abort_if($index === false, 404);
                $portal['messages'][$index]['acks'][(string) $request->user()->id] = ['name' => $request->user()->name, 'at' => $now];
            }
            if ($data['action'] === 'review') {
                $index = array_search($data['id'], array_column($portal['reports'], 'id'), true);
                abort_if($index === false, 404);
                $portal['reports'][$index]['status'] = $data['status'];
                if (filled($data['response'] ?? '')) {
                    $portal['reports'][$index]['responses'][] = ['message' => $data['response'], 'author' => $request->user()->name, 'at' => $now];
                }
            }
            $org->update(['portal_data' => json_encode($portal)]);
        });

        return redirect('/workerportal')->with('status', 'Portal updated.');
    }
}
