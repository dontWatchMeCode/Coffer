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

    $clicked = false;
    $deadline = microtime(true) + 5;

    while (microtime(true) < $deadline) {
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

        if ($clicked === true) {
            break;
        }

        usleep(100_000);
    }

    expect($clicked)->toBeTrue();

    waitForBrowserPath($page, '/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit')
        ->assertNoJavaScriptErrors();
});

it('keeps the search input anchored as result counts change', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    foreach (range(1, 8) as $number) {
        Task::factory()->create([
            'team_id' => $team->id,
            'project_id' => $project->id,
            'title' => 'Stable Search Task '.$number,
        ]);
    }

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/dashboard')->assertNoJavaScriptErrors();
    $page->script(<<<'JS'
        (() => {
            const style = document.createElement('style');
            style.nonce = document.querySelector('script[nonce]').nonce;
            style.textContent = '*, *::before, *::after { animation: none !important; transition: none !important; }';
            document.head.append(style);
        })()
    JS);
    $page->click('[data-testid="global-search-trigger"]');

    $inputTop = $page->script('document.querySelector("[data-testid=global-search-input]").getBoundingClientRect().top');

    foreach (['Stable Search', 'Stable Search Task 1', 'NoMatchingStableSearchRecord'] as $query) {
        $page->fill('[data-testid="global-search-input"]', $query);
        waitForBrowserText($page, $query === 'NoMatchingStableSearchRecord' ? 'No results found' : 'Stable Search Task 1');
        waitForBrowserText($page, $query === 'Stable Search' ? '8 results' : ($query === 'Stable Search Task 1' ? '1 result' : 'No results found'));

        $updatedTop = $page->script('document.querySelector("[data-testid=global-search-input]").getBoundingClientRect().top');

        expect(abs($updatedTop - $inputTop))->toBeLessThan(1);
    }

    $page->assertNoJavaScriptErrors();
});
