<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\HraReportExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HraDocumentController extends Controller
{
    public function store(Request $request, Organization $organization, HraReportExtractor $extractor): JsonResponse
    {
        abort_unless($organization->canEdit($request->user()), 403);
        abort_unless($organization->hasAccess() || $request->user()->isSuperAdmin(), 403);
        $request->validate(['document' => 'required|file|mimes:pdf,docx|max:15360|extensions:pdf,docx']);
        $file = $request->file('document');
        $id = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('hra/'.$organization->id, $id.'.'.$extension, 'local');
        $document = ['id' => $id, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 200), 'extension' => $extension];
        Storage::disk('local')->put('hra/'.$organization->id.'/'.$id.'.json', json_encode($document));
        try {
            $draft = $extractor->extract(Storage::disk('local')->path($path), $extension, $document['name']);
        } catch (\Throwable $exception) {
            report($exception);
            $draft = $extractor->draft('', $document['name']) + ['warning' => 'Extraction is unavailable. The report is attached; enter its details manually.'];
        }

        return response()->json(['document' => $document, 'draft' => $draft]);
    }

    public function download(Request $request, Organization $organization, string $document): BinaryFileResponse
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);
        abort_unless(Str::isUuid($document), 404);
        $disk = Storage::disk('local');
        $metadata = 'hra/'.$organization->id.'/'.$document.'.json';
        abort_unless($disk->exists($metadata), 404);
        $file = json_decode($disk->get($metadata), true);

        return response()->download($disk->path('hra/'.$organization->id.'/'.$document.'.'.$file['extension']), $file['name'], ['X-Content-Type-Options' => 'nosniff']);
    }
}
