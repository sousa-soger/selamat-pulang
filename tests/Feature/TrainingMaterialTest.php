<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainingMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_quiz_scoring_download_permissions_and_revision_reset(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $worker = User::factory()->create();
        $other = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $owner->id, 'admin_provisioned' => true]);
        $org->users()->attach([$worker->id => ['role' => 'viewer'], $other->id => ['role' => 'viewer']]);
        $payload = ['title' => 'Safe lifting', 'description' => 'Plan before lifting', 'participants' => [$worker->id], 'questions' => [['prompt' => 'First action?', 'options' => ['Plan', 'Rush', 'Ignore', 'Skip'], 'correct' => 0]], 'attachments' => [UploadedFile::fake()->create('guide.pdf', 10, 'application/pdf')]];
        $this->actingAs($worker)->post(route('materials.store', $org), $payload)->assertForbidden();
        $this->actingAs($owner)->post(route('materials.store', $org), $payload)->assertRedirect('/training');
        $material = array_values(json_decode($org->fresh()->training_data, true))[0];
        $download = route('materials.download', [$org, $material['id'], $material['attachments'][0]['id']]);
        $this->actingAs($other)->get($download)->assertForbidden();
        $this->post(route('materials.complete', [$org, $material['id']]), ['revision' => '1', 'read' => 1, 'answers' => [0]])->assertForbidden();
        $this->actingAs($worker)->get($download)->assertOk();
        $this->withSession(['organization_id' => $org->id])->get('/learning')->assertOk()->assertSee('Safe lifting')->assertDontSee('Correct answer');
        $url = route('materials.complete', [$org, $material['id']]);
        $this->post($url, ['revision' => '1', 'read' => 1, 'answers' => [1]])->assertRedirect();
        $this->assertFalse(json_decode($org->fresh()->training_data, true)[$material['id']]['completions'][$worker->id]['passed']);
        $this->post($url, ['revision' => '1', 'read' => 1, 'answers' => [0]])->assertRedirect();
        $this->assertTrue(json_decode($org->fresh()->training_data, true)[$material['id']]['completions'][$worker->id]['passed']);
        unset($payload['attachments']);
        $payload['id'] = $material['id'];
        $payload['revision'] = '1';
        $this->actingAs($owner)->post(route('materials.store', $org), $payload)->assertRedirect();
        $this->assertEmpty(json_decode($org->fresh()->training_data, true)[$material['id']]['completions']);
        $this->actingAs($worker)->from('/learning')->post($url, ['revision' => '1', 'read' => 1, 'answers' => [0]])->assertRedirect('/learning')->assertSessionHasErrors('training');
        $this->assertEmpty(json_decode($org->fresh()->training_data, true)[$material['id']]['completions']);
        $this->actingAs($owner)->withSession(['organization_id' => $org->id])->get('/training')->assertOk()->assertSee('Participants &amp; progress',false);
    }
}
