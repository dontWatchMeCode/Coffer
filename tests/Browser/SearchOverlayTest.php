<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

it('searches globally and navigates to a result', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Searchable Project',
    ]);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'title' => 'Searchable Browser Task',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/dashboard')
        ->click('[data-testid="global-search-trigger"]')
        ->fill('[data-testid="global-search-input"]', 'Searchable Browser');

    waitForBrowserText($page, 'Searchable Browser Task');

    $clicked = $page->script(<<<'JS'
        (() => {
            const element = Array.from(document.querySelectorAll('[data-testid="global-search-result"]'))
                .find((element) => element.innerText.includes('Searchable Browser Task'));

            if (!element) {
                return false;
            }

            element.click();

            return true;
        })()
    JS);

    expect($clicked)->not->toBeFalse();

    waitForBrowserPath($page, '/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit')
        ->assertNoJavaScriptErrors();
});
