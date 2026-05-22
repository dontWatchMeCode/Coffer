<?php

use App\Models\Project;
use App\Models\User;

it('tasks page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Project::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/tasks')
        ->assertSee('Tasks')
        ->assertSee('Choose a project to work on or create a new one.')
        ->assertNoJavaScriptErrors();
});

it('tasks page shows existing projects', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Website Redesign',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/tasks')
        ->assertSee('Website Redesign')
        ->assertNoJavaScriptErrors();
});

it('task project detail page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Mobile App',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/tasks/'.$project->id)
        ->resize(1600, 900)
        ->assertSee('Mobile App')
        ->assertNoJavaScriptErrors();

    $sidebarMetrics = $page->script(<<<'JS'
        (() => {
            const sidebar = document.querySelector('[data-testid="editor-sidebar"]');

            if (!sidebar) {
                return null;
            }

            const style = window.getComputedStyle(sidebar);

            return {
                overflowY: style.overflowY,
                position: style.position,
            };
        })()
    JS);

    expect($sidebarMetrics)->not->toBeNull()
        ->and($sidebarMetrics['position'])->toBe('sticky')
        ->and($sidebarMetrics['overflowY'])->toBe('auto');
});
