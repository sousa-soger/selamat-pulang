<?php

namespace Tests\Feature;

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

    public function test_home_separates_personal_tools_from_organization_reports(): void
    {
        $response = $this->get('/')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        $this->assertSame(1, $xpath->query('//section[@id="dashboard"]//a[@href="/personal"]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="dashboard"]//a[@href="/organization"]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="dashboard"]//*[@id="metricCompliance"]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="organization"]//*[@id="metricCompliance"]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="organization"]//details[not(@open)]')->length);
    }

    public function test_every_workspace_module_can_be_opened_directly(): void
    {
        foreach (config('adapt360') as $screen => $metadata) {
            $this->get('/'.$screen)->assertOk()
                ->assertSee('data-screen="'.$screen.'"', false)
                ->assertSee('adapt360-logo.webp');
        }
    }

    public function test_existing_bookmarks_redirect_to_their_replacement(): void
    {
        foreach (['profile' => 'workplace', 'health' => 'hra', 'languages' => 'workerportal', 'oshwa' => 'solve'] as $old => $new) {
            $this->get('/'.$old)->assertRedirect('/'.$new);
        }
        $this->get('/unknown-module')->assertNotFound();
    }
}
