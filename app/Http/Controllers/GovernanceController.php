<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GovernanceController extends Controller
{
    private function authorizeMember(Request $request, Organization $organization): void
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorizeMember($request, $organization);
        $data = $request->validate(['action' => 'required|in:submit,approve,reject', 'id' => 'nullable|string|max:100', 'kind' => 'required_if:action,submit|in:policy,role', 'title' => 'required_if:action,submit|string|max:200', 'subtitle' => 'nullable|string|max:300', 'description' => 'required_if:action,submit|string|max:20000', 'mode' => 'nullable|in:request,publish', 'revision' => 'nullable|integer|min:0', 'attachments' => 'nullable|array|max:10', 'attachments.*' => 'file|max:20480']);
        $editor = $organization->canEdit($request->user());
        if ($data['action'] !== 'submit') {
            abort_unless($editor, 403);
        }
        $files = [];
        try {
            foreach ($request->file('attachments', []) as $file) {
                $id = (string) Str::uuid();
                $path = $file->storeAs('governance/'.$organization->id, $id, 'local');
                $files[] = ['id' => $id, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 200), 'path' => $path];
            }
            DB::transaction(function () use ($organization, $request, $data, $editor, $files) {
                $org = Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
                $register = json_decode($org->governance_data ?? '{}', true) ?: ['entries' => [], 'requests' => []];
                if ($data['action'] === 'submit') {
                    $id = $data['id'] ?? (string) Str::uuid();
                    $previous = $register['entries'][$id] ?? null;
                    $entry = ['id' => $id, 'kind' => $data['kind'], 'title' => $data['title'], 'subtitle' => $data['subtitle'] ?? '', 'description' => $data['description'], 'attachments' => array_merge($previous['attachments'] ?? [], $files), 'revision' => ($previous['revision'] ?? 0) + 1, 'author' => $request->user()->name, 'at' => now()->toIso8601String()];
                    if ($editor && ($data['mode'] ?? '') === 'publish') {
                        abort_unless(($previous['revision'] ?? 0) == ($data['revision'] ?? 0), 409, 'This entry changed. Reload before editing.');
                        $register['entries'][$id] = $entry;
                    } else {
                        $register['requests'][] = ['id' => (string) Str::uuid(), 'entry' => $entry, 'base' => $previous['revision'] ?? 0, 'status' => 'Pending', 'author' => $request->user()->name];
                    }
                } else {
                    $index = array_search($data['id'], array_column($register['requests'], 'id'), true);
                    abort_if($index === false, 404);
                    $proposal = &$register['requests'][$index];
                    abort_unless($proposal['status'] === 'Pending', 409);
                    if ($data['action'] === 'approve') {
                        $current = $register['entries'][$proposal['entry']['id']] ?? null;
                        abort_unless(($current['revision'] ?? 0) === $proposal['base'], 409, 'The entry changed since this request. Submit an updated request.');
                        $register['entries'][$proposal['entry']['id']] = $proposal['entry'];
                    }
                    $proposal['status'] = $data['action'] === 'approve' ? 'Approved' : 'Rejected';
                    $proposal['reviewer'] = $request->user()->name;
                }
                $org->update(['governance_data' => json_encode($register)]);
            });
        } catch (\Throwable $error) {
            foreach ($files as $file) {
                Storage::disk('local')->delete($file['path']);
            }throw $error;
        }

        return redirect('/roles')->with('status', 'Policy and role register updated.');
    }

    public function download(Request $request, Organization $organization, string $document)
    {
        $this->authorizeMember($request, $organization);
        $data = json_decode($organization->governance_data ?? '{}', true) ?: [];
        $files = [];
        foreach ($data['entries'] ?? [] as $entry) {
            $files = array_merge($files, $entry['attachments'] ?? []);
        }
        foreach ($data['requests'] ?? [] as $proposal) {
            $files = array_merge($files, $proposal['entry']['attachments'] ?? []);
        }
        $file = collect($files)->firstWhere('id', $document);
        abort_unless($file, 404);

        return Storage::disk('local')->download($file['path'], $file['name'], ['X-Content-Type-Options' => 'nosniff']);
    }
}
