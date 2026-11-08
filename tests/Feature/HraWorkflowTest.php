<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\HraReportExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HraWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_organization_has_no_assessments_and_saves_items_and_steps(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id, 'admin_provisioned' => true]);
        $this->actingAs($user)->withSession(['organization_id' => $org->id])->get('/hra')->assertOk()->assertSee('+ Add finding')->assertSee('Upload report');
        $this->assertEmpty(json_decode($org->workspace_data ?? '{}', true)['hraRecords'] ?? []);
        $record = ['id' => 'hra-1', 'title' => 'Workshop noise', 'type' => 'noise', 'priority' => 'High', 'status' => 'Draft', 'items' => [['title' => 'Review blower enclosure', 'priority' => 'High', 'status' => 'Open', 'steps' => [['title' => 'Consult assessor', 'description' => 'Review the report recommendation.']]]], 'documents' => []];
        $this->putJson(route('workspace.save', $org), ['version' => 0, 'data' => ['hraRecords' => [$record]]])->assertOk();
        $this->assertSame('Consult assessor', json_decode($org->fresh()->workspace_data, true)['hraRecords'][0]['items'][0]['steps'][0]['title']);
        $record['items'][0]['steps'][0]['title'] = '';
        $this->putJson(route('workspace.save', $org), ['version' => 1, 'data' => ['hraRecords' => [$record]]])->assertUnprocessable();
    }

    public function test_upload_is_private_and_download_is_scoped_to_organization(): void
    {
        Storage::fake('local');
        Process::fake([Process::result(output: json_encode(['text' => "Noise Risk Assessment\nDate of Assessment: 29 – 30 June 2022\nASSESSOR: Group 2\nExecutive Summary\nNoise exposure was assessed at the warehouse and training area to identify sources and review controls.\nWorking Area"]))]);
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id, 'admin_provisioned' => true]);
        $result = $this->actingAs($user)->postJson(route('hra.documents.store', $org), ['document' => UploadedFile::fake()->create('noise.pdf', 10, 'application/pdf')])->assertOk()->assertJsonPath('draft.assessedOn', '2022-06-29')->assertJsonPath('draft.priority', 'Unassigned')->assertJsonPath('draft.due', '');
        $id = $result->json('document.id');
        Storage::disk('local')->assertExists('hra/'.$org->id.'/'.$id.'.pdf');
        $this->get(route('hra.documents.download', [$org, $id]))->assertDownload('noise.pdf');
        $this->actingAs(User::factory()->create())->get(route('hra.documents.download', [$org, $id]))->assertForbidden();
        $this->postJson(route('hra.documents.store', $org), ['document' => UploadedFile::fake()->create('noise.pdf', 10, 'application/pdf')])->assertForbidden();
    }

    public function test_viewers_cannot_upload_and_invalid_files_are_rejected(): void
    {
        Process::fake();
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $owner->id, 'admin_provisioned' => true]);
        $org->users()->attach($viewer, ['role' => 'viewer']);
        $this->actingAs($viewer)->postJson(route('hra.documents.store', $org), ['document' => UploadedFile::fake()->create('noise.pdf', 10, 'application/pdf')])->assertForbidden();
        $this->actingAs($owner)->postJson(route('hra.documents.store', $org), ['document' => UploadedFile::fake()->create('payload.exe', 10)])->assertUnprocessable();
        $this->postJson(route('hra.documents.store', $org), ['document' => UploadedFile::fake()->create('large.pdf', 16000, 'application/pdf')])->assertUnprocessable();
        Process::assertNothingRan();
    }

    public function test_extraction_failure_keeps_attachment_for_manual_entry(): void
    {
        Storage::fake('local');
        Process::fake([Process::result(exitCode: 1)]);
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id, 'admin_provisioned' => true]);
        $this->actingAs($user)->postJson(route('hra.documents.store', $org), ['document' => UploadedFile::fake()->create('scan.pdf', 10, 'application/pdf')])->assertOk()->assertJsonPath('draft.assessedOn', '')->assertJsonPath('draft.priority', 'Unassigned')->assertJsonStructure(['document' => ['id'], 'draft' => ['warning']]);
    }

    public function test_scoring_examples_and_calibration_dates_do_not_become_findings(): void
    {
        $draft = app(HraReportExtractor::class)->draft("Advanced Ergonomics Risk Assessment\nCalibration validity: 23 March 2023\nSCORING TABLE\nHigh risk: action required immediately", 'aera.pdf');
        $this->assertSame('ergo', $draft['type']);
        $this->assertSame('', $draft['assessedOn']);
        $this->assertSame('Unassigned', $draft['priority']);
        $this->assertSame([], $draft['items']);
    }

    public function test_supplied_sample_reports_when_available_locally(): void
    {
        $directory = getenv('HRA_SAMPLE_DIR');
        if (! $directory) {
            $this->markTestSkipped('Set HRA_SAMPLE_DIR to exercise local sample reports.');
        }
        foreach ([['Noise Risk Assessment Practical Exercise R1.docx', 'docx', 'noise', '2022-06-29'], ['AERA Report.pdf', 'pdf', 'ergo', '2022-07-01'], ['Lai Jia Le - Initial Ergonomic Risk Assessment.pdf', 'pdf', 'ergo', '2021-04-01']] as [$filename, $extension, $type, $date]) {
            $draft = app(HraReportExtractor::class)->extract($directory.'/'.$filename, $extension, $filename);
            $this->assertSame($type, $draft['type'], $filename);
            $this->assertSame($date, $draft['assessedOn'], $filename);
            $this->assertNotEmpty($draft['sourceExcerpt']);
        }
    }
}
