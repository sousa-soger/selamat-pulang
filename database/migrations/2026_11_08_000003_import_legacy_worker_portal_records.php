<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizations')->orderBy('id')->each(function ($org) {
            if ($org->portal_data) {
                return;
            }
            $workspace = json_decode($org->workspace_data ?? '{}', true) ?: [];
            $portal = ['messages' => [], 'reports' => []];
            foreach ($workspace['safetyMessages'] ?? [] as $language => $message) {
                if (! is_string($message) || trim($message) === '') {
                    continue;
                }$portal['messages'][] = ['id' => (string) Str::uuid(), 'message' => $message, 'language' => $language, 'author' => 'Imported message', 'at' => $org->updated_at, 'acks' => []];
            }
            foreach ($workspace['incidents'] ?? [] as $report) {
                if (($report['type'] ?? '') !== 'Worker Report') {
                    continue;
                }$portal['reports'][] = ['id' => (string) Str::uuid(), 'message' => $report['event'] ?? '', 'type' => 'Hazard', 'location' => $report['area'] ?? '', 'author' => 'Anonymous', 'at' => $org->updated_at, 'status' => 'New', 'responses' => []];
            }
            DB::table('organizations')->where('id', $org->id)->update(['portal_data' => json_encode($portal)]);
        });
    }

    public function down(): void {}
};
