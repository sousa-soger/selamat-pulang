<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SafetyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachments_are_private_and_viewers_cannot_upload(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $owner->id, 'admin_provisioned' => true]);
        $org->users()->attach($viewer, ['role' => 'viewer']);
        $url = route('safety-attachments.store', $org);
        $this->actingAs($viewer)->postJson($url, ['document' => UploadedFile::fake()->create('evidence.pdf', 10)])->assertForbidden();
        $upload = $this->actingAs($owner)->postJson($url, ['document' => UploadedFile::fake()->create('evidence.pdf', 10)])->assertOk();
        $download = route('safety-attachments.download', [$org, $upload->json('id')]);
        $this->actingAs($viewer)->get($download)->assertOk();
        $this->actingAs($other)->get($download)->assertForbidden();
    }

    public function test_permit_requires_real_member_risk_controls_and_expiry(): void
    {
        $owner = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $owner->id, 'admin_provisioned' => true]);
        $org->users()->attach($owner, ['role' => 'editor']);
        $risk = ['id' => 'HIR-1', 'jmsId' => 'JMS-1', 'step' => 'Isolate', 'activity' => 'Isolate', 'l' => 2, 's' => 3, 'status' => 'Current'];
        $baseline = ['hirarc' => [$risk], 'permits' => []];
        $org->update(['workspace_data' => json_encode($baseline), 'workspace_version' => 0]);
        $url = route('workspace.save', $org);
        $this->actingAs($owner)->putJson($url, ['version' => 0, 'data' => array_merge($baseline, ['permits' => [['id' => 'PTW-1', 'status' => 'Active']]])])->assertUnprocessable();
        $permit = ['id' => 'PTW-1', 'status' => 'Pending', 'memberId' => $owner->id, 'hir' => 'HIR-1', 'controls' => 'Isolate and verify', 'until' => now()->addDay()->format('Y-m-d\TH:i')];
        $this->putJson($url, ['version' => 0, 'data' => array_merge($baseline, ['permits' => [$permit]])])->assertOk();
        $permit['status'] = 'Active';
        $permit['until'] = now()->subDay()->format('Y-m-d\TH:i');
        $this->putJson($url, ['version' => 1, 'data' => array_merge($baseline, ['permits' => [$permit]])])->assertUnprocessable();
    }

    public function test_safety_pages_render_with_new_navigation(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id, 'admin_provisioned' => true]);
        $org->users()->attach($user, ['role' => 'editor']);
        foreach (['/ptw', '/incidents', '/solve', '/excel', '/next'] as $path) {
            $this->actingAs($user)->withSession(['organization_id' => $org->id])->get($path)->assertOk();
        }
    }
}
