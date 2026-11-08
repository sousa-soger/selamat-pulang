<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Adapt360Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_home_links_to_each_organization_area_and_shows_sortable_alerts_and_calendar_cards(): void
    {
        $organization = Organization::factory()->create(['owner_id' => auth()->id()]);
        $organization->users()->attach(auth()->id());
        $response = $this->withSession(['organization_id' => $organization->id])->get('/')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach (['workplace', 'legal', 'committee', 'hirarc', 'hra', 'capa', 'fives', 'solve', 'excel', 'mentor'] as $screen) {
            $this->assertGreaterThanOrEqual(1, $xpath->query('//section[@id="dashboard"]//a[@href="/'.$screen.'"]')->length);
        }
        $response->assertSee('Sort alerts')->assertSee('My order')->assertSee('Coming soon');
        $this->assertSame(2, $xpath->query('//*[@id="homeDeck"]/section')->length);
        $this->assertSame(1, $xpath->query('//*[@id="homeCalendarGrid"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="homeTodoList"]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="dashboard"]//*[@id="metricCompliance"]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="organization"]//*[@id="metricCompliance"]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="organization"]//*[@id="dashCalendar"]')->length);
    }

    public function test_every_workspace_module_can_be_opened_directly(): void
    {
        foreach (array_diff_key(config('adapt360'), array_flip(['workplace', 'committee', 'calendar', 'personal', 'mentor', 'emergency', 'capa'])) as $screen => $metadata) {
            $this->get('/'.$screen)->assertOk()
                ->assertSee('data-screen="'.$screen.'"', false)
                ->assertSee('adapt360-logo.webp');
        }
    }

    public function test_existing_bookmarks_redirect_to_their_replacement(): void
    {
        foreach (['profile' => 'organization', 'workplace' => 'organization', 'committee' => 'organization', 'calendar' => 'organization', 'emergency' => 'organization', 'capa' => 'organization', 'personal' => 'learning', 'mentor' => 'learning', 'health' => 'hra', 'languages' => 'workerportal', 'oshwa' => 'solve'] as $old => $new) {
            $this->get('/'.$old)->assertRedirect('/'.$new);
        }
        $this->get('/unknown-module')->assertNotFound();
    }

    public function test_hirarc_requires_a_previously_saved_jms_step(): void
    {
        $organization = Organization::factory()->create(['owner_id' => auth()->id(), 'admin_provisioned' => true]);
        $row = ['id' => 'HIR-1', 'jmsId' => 'jms-1', 'step' => 'Inspect machine'];
        $statement = ['id' => 'jms-1', 'activity' => 'Maintenance', 'preparedBy' => 'Technical team', 'steps' => ['Inspect machine']];

        $this->putJson(route('workspace.save', $organization), ['version' => 0, 'data' => ['jms' => [$statement], 'hirarc' => [$row]]])->assertUnprocessable();
        $this->putJson(route('workspace.save', $organization), ['version' => 0, 'data' => ['jms' => [$statement], 'hirarc' => []]])->assertOk();
        $this->putJson(route('workspace.save', $organization), ['version' => 1, 'data' => ['jms' => [$statement], 'hirarc' => [$row]]])->assertOk();
    }
}
