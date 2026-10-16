<?php

use App\Enums\TaskStatus;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

it('preserves appearance and workspace controls across screen sizes', function (int $width, string $appearance) {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id, 'name' => 'Design migration']);
    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'title' => 'Review the workspace design with a long task title that wraps on mobile',
        'status' => TaskStatus::InProgress,
        'due_at' => now()->subDay(),
    ]);
    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Workspace review meeting',
        'date' => now()->toDateString(),
        'time' => '10:00',
    ]);
    $this->actingAs($user);

    $page = visit('/settings/appearance')->resize($width, 900);
    $page->press($appearance);

    expect($page->script('document.documentElement.classList.contains("dark")'))
        ->toBe($appearance === 'Dark');

    if ($width < 1081) {
        $page->press('Toggle sidebar');
    }

    $page->click('Dashboard');
    waitForBrowserPath($page, '/'.$team->slug.'/dashboard');

    $page->assertSee('Open tasks')
        ->assertSee('Recent activity')
        ->assertSee('Review the workspace design with a long task title that wraps on mobile')
        ->assertSee('Overdue')
        ->assertSee('Workspace review meeting')
        ->assertNoJavaScriptErrors();

    $metrics = $page->script(<<<'JS'
        (() => {
            const card = document.querySelector('[data-slot="card"]');
            return {
                overflow: document.documentElement.scrollWidth > window.innerWidth,
                background: getComputedStyle(document.body).backgroundColor,
                font: getComputedStyle(document.body).fontFamily,
                radius: getComputedStyle(card).borderRadius,
                selected: document.querySelector('[data-sidebar="menu-button"][data-active="true"]')?.textContent.trim(),
            };
        })()
    JS);

    expect($metrics['overflow'])->toBeFalse()
        ->and($metrics['background'])->toBe($appearance === 'Dark' ? 'rgb(40, 42, 54)' : 'rgb(244, 243, 248)')
        ->and($metrics['font'])->toContain('ui-monospace')
        ->and($metrics['radius'])->toBe('0px');

    if ($width >= 1081) {
        expect($metrics['selected'])->toBe('Dashboard');
    }

    if ($width < 1081) {
        $page->press('Toggle sidebar');
    }

    $page->click('[data-testid="global-search-trigger"]')
        ->assertVisible('[data-slot="dialog-content"]')
        ->assertVisible('[data-testid="global-search-input"]')
        ->fill('[data-testid="global-search-input"]', 'Design migration');

    waitForBrowserText($page, 'Design migration');
    $page->assertSee('Design migration')
        ->assertNoJavaScriptErrors();
})->with([
    'desktop dark' => [1280, 'Dark'],
    'desktop light' => [1280, 'Light'],
    'mobile dark' => [390, 'Dark'],
    'mobile light' => [390, 'Light'],
]);
