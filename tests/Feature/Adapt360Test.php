<?php

namespace Tests\Feature;

use Tests\TestCase;

class Adapt360Test extends TestCase
{
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
