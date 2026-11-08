<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrainingMaterialController extends Controller
{
    private function member(Request $request, Organization $org): void
    {
        abort_unless($org->canManage($request->user()) || $org->users()->whereKey($request->user()->id)->exists(), 403);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->member($request, $organization);
        abort_unless($organization->canEdit($request->user()), 403);
        $data = $request->validate(['id' => 'nullable|uuid', 'revision' => 'nullable|integer|min:0', 'title' => 'required|string|max:200', 'description' => 'required|string|max:20000', 'due' => 'nullable|date_format:Y-m-d', 'participants' => 'required|array|min:1', 'participants.*' => 'integer|distinct', 'attachments' => 'nullable|array|max:10', 'attachments.*' => 'file|max:20480', 'questions' => 'nullable|array|max:30', 'questions.*.prompt' => 'required|string|max:1000', 'questions.*.options' => 'required|array|size:4', 'questions.*.options.*' => 'required|string|max:500', 'questions.*.correct' => 'required|integer|between:0,3']);
        $valid = $organization->users()->pluck('users.id')->push($organization->owner_id)->unique()->all();
        abort_if(array_diff($data['participants'], $valid), 422, 'Select current organization members.');
        $files = [];
        try {
            foreach ($request->file('attachments', []) as $file) {
                $id = (string) Str::uuid();
                $files[] = ['id' => $id, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 200), 'path' => $file->storeAs('training/'.$organization->id, $id, 'local')];
            }
            DB::transaction(function () use ($organization, $request, $data, $files) {
                $org = Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
                $register = json_decode($org->training_data ?? '{}', true) ?: [];
                $id = $data['id'] ?? (string) Str::uuid();
                $previous = $register[$id] ?? null;
                if (isset($data['id'])) {
                    abort_unless($previous, 404);
                    if ((int) ($data['revision'] ?? 0) !== (int) $previous['revision']) {
                        throw ValidationException::withMessages(['training' => 'Training changed since you opened it. Review the latest version before publishing.']);
                    }
                }
                $register[$id] = ['id' => $id, 'title' => $data['title'], 'description' => $data['description'], 'due' => $data['due'] ?? null, 'participants' => array_map('intval', $data['participants']), 'questions' => $data['questions'] ?? [], 'attachments' => array_merge($previous['attachments'] ?? [], $files), 'revision' => ($previous['revision'] ?? 0) + 1, 'completions' => [], 'author' => $request->user()->name, 'at' => now()->toIso8601String()];
                $org->update(['training_data' => json_encode($register)]);
            });
        } catch (\Throwable $error) {
            foreach ($files as $file) {
                Storage::disk('local')->delete($file['path']);
            }throw $error;
        }

        return redirect('/training')->with('status', 'Training published. Assigned learners can now open it in Learning.');
    }

    public function complete(Request $request, Organization $organization, string $material)
    {
        $this->member($request, $organization);
        $data = $request->validate(['revision' => 'required|integer', 'read' => 'accepted', 'answers' => 'nullable|array', 'answers.*' => 'integer|between:0,3']);
        $passed = false;
        DB::transaction(function () use ($organization, $request, $material, $data, &$passed) {
            $org = Organization::whereKey($organization->id)->lockForUpdate()->firstOrFail();
            $register = json_decode($org->training_data ?? '{}', true) ?: [];
            abort_unless(isset($register[$material]), 404);
            $entry = &$register[$material];
            abort_unless(in_array($request->user()->id, $entry['participants'], true), 403);
            if ((int) $entry['revision'] !== (int) $data['revision']) {
                throw ValidationException::withMessages(['training' => 'Training changed since you opened it. Review the latest material and submit again.']);
            }
            $score = 0;
            foreach ($entry['questions'] as $index => $question) {
                if (isset($data['answers'][$index]) && (int) $data['answers'][$index] === (int) $question['correct']) {
                    $score++;
                }
            }$passed = $score === count($entry['questions']);
            $entry['completions'][(string) $request->user()->id] = ['passed' => $passed, 'score' => $score, 'total' => count($entry['questions']), 'at' => now()->toIso8601String()];
            $org->update(['training_data' => json_encode($register)]);
        });

        return redirect('/learning')->with('status', $passed ? 'Training completed.' : 'Quiz not passed yet. Review the material and try again.');
    }

    public function download(Request $request, Organization $organization, string $material, string $document)
    {
        $this->member($request, $organization);
        $entry = (json_decode($organization->training_data ?? '{}', true) ?: [])[$material] ?? null;
        abort_unless($entry, 404);
        abort_unless($organization->canEdit($request->user()) || in_array($request->user()->id, $entry['participants'], true), 403);
        $file = collect($entry['attachments'])->firstWhere('id', $document);
        abort_unless($file, 404);

        return Storage::disk('local')->download($file['path'], $file['name'], ['X-Content-Type-Options' => 'nosniff']);
    }
}
