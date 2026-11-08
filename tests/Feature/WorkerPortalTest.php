<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_reporting_acknowledgements_and_review_permissions(): void
    {
        $owner = User::factory()->create();
        $worker = User::factory()->create();
        $other = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $owner->id, 'admin_provisioned' => true]);
        $org->users()->attach($worker, ['role' => 'viewer']);
        $url = route('portal.store', $org);
        $this->actingAs($worker)->post($url, ['action' => 'publish', 'language' => 'en', 'message' => 'Hello'])->assertForbidden();
        $this->actingAs($other)->post($url, ['action' => 'report', 'type' => 'Hazard', 'message' => 'Unsafe'])->assertForbidden();
        $this->actingAs($owner)->post($url, ['action' => 'publish', 'language' => 'en', 'message' => 'Use PPE'])->assertRedirect('/workerportal');
        $portal = json_decode($org->fresh()->portal_data, true);
        $id = $portal['messages'][0]['id'];
        $this->actingAs($worker)->post($url, ['action' => 'ack', 'id' => $id])->assertRedirect();
        $this->post($url, ['action' => 'ack', 'id' => $id])->assertRedirect();
        $this->assertCount(1, json_decode($org->fresh()->portal_data, true)['messages'][0]['acks']);
        $this->post($url, ['action' => 'report', 'type' => 'Hazard', 'message' => 'Blocked exit', 'anonymous' => 1])->assertRedirect();
        $report = json_decode($org->fresh()->portal_data, true)['reports'][0];
        $this->assertSame('Anonymous', $report['author']);
        $this->assertArrayNotHasKey('user_id', $report);
        $this->post($url, ['action' => 'review', 'id' => $report['id'], 'status' => 'Resolved'])->assertForbidden();
        $this->actingAs($owner)->post($url, ['action' => 'review', 'id' => $report['id'], 'status' => 'Resolved', 'response' => 'Cleared'])->assertRedirect();
        $this->actingAs($worker)->withSession(['organization_id' => $org->id])->get('/workerportal')->assertOk()->assertSee('Blocked exit')->assertSee('Cleared')->assertSee('Use PPE');
    }
}
