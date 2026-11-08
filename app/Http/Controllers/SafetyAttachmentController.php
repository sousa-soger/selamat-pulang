<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SafetyAttachmentController extends Controller
{
    public function store(Request $request, Organization $organization)
    {
        abort_unless($organization->canEdit($request->user()) && ($organization->hasAccess() || $request->user()->isSuperAdmin()), 403);
        $request->validate(['document' => 'required|file|max:20480']);
        $file = $request->file('document');
        $id = (string) Str::uuid();
        $path = $file->storeAs('safety/'.$organization->id, $id, 'local');
        $metadata = ['id' => $id, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 200)];
        Storage::disk('local')->put($path.'.json', json_encode($metadata));

        return response()->json($metadata);
    }

    public function download(Request $request, Organization $organization, string $document)
    {
        abort_unless($organization->canManage($request->user()) || $organization->users()->whereKey($request->user()->id)->exists(), 403);
        abort_unless(Str::isUuid($document), 404);
        $path = 'safety/'.$organization->id.'/'.$document;
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path.'.json') && $disk->exists($path), 404);
        $metadata = json_decode($disk->get($path.'.json'), true);

        return $disk->download($path, $metadata['name'], ['X-Content-Type-Options' => 'nosniff']);
    }
}
