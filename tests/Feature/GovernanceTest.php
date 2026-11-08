<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_proposals_require_editor_approval_and_attachments_are_private(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $worker = User::factory()->create();
        $outsider = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $owner->id, 'admin_provisioned' => true]);
        $org->users()->attach($worker, ['role' => 'viewer']);
        $url = route('governance.store', $org);
        $this->actingAs($worker)->post($url, ['action' => 'submit', 'mode' => 'publish', 'kind' => 'policy', 'title' => 'Emergency policy', 'description' => 'Evacuate safely', 'attachments' => [UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf')]])->assertRedirect('/roles');
        $data = json_decode($org->fresh()->governance_data, true);
        $this->assertEmpty($data['entries']);
        $proposal = $data['requests'][0];
        $this->assertSame('Pending', $proposal['status']);
        $download = route('governance.download', [$org, $proposal['entry']['attachments'][0]['id']]);
        $this->actingAs($outsider)->get($download)->assertForbidden();
        $this->actingAs($worker)->get($download)->assertOk();
        $this->post($url, ['action' => 'approve', 'id' => $proposal['id']])->assertForbidden();
        $this->actingAs($owner)->post($url, ['action' => 'approve', 'id' => $proposal['id']])->assertRedirect();
        $this->assertCount(1, json_decode($org->fresh()->governance_data, true)['entries']);
        $this->withSession(['organization_id' => $org->id])->get('/roles')->assertOk()->assertSee('Emergency policy')->assertSee('plan.pdf');
    }
}
