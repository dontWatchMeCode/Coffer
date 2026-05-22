<?php

use App\Models\Contact;
use App\Models\Project;
use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

it('task edit page shows linked records, tags, and supports panel search', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'title' => 'Panel Task',
    ]);
    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Panel Contact',
    ]);
    $linkableContact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Linkable Browser Contact',
    ]);
    $tag = Tag::factory()->create([
        'team_id' => $team->id,
        'name' => 'Important',
        'slug' => 'important',
    ]);
    $searchableTag = Tag::factory()->create([
        'team_id' => $team->id,
        'name' => 'Existing Browser Tag',
        'slug' => 'existing-browser-tag',
    ]);

    RecordLink::create([
        'team_id' => $team->id,
        'left_type' => $task->linkableType(),
        'left_id' => $task->id,
        'right_type' => $contact->linkableType(),
        'right_id' => $contact->id,
    ]);

    $task->recordTags()->attach($tag->id);

    $this->actingAs($user);

    $editUrl = '/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit';

    visit($editUrl)
        ->assertSee('Linked Records')
        ->assertSee('Panel Contact')
        ->assertSee('Tags')
        ->assertSee('Important');

    $page = visit($editUrl)
        ->fill('[data-testid="record-link-search-input"]', 'Linkable Browser');

    waitForBrowserText($page, 'Linkable Browser Contact');

    $page = visit($editUrl)
        ->fill('[data-testid="record-tag-search-input"]', 'Existing Browser');

    waitForBrowserText($page, 'Existing Browser Tag')
        ->assertNoJavaScriptErrors();
});

it('task edit page scrolls an overflowing sidebar independently', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'title' => 'Scrollable Sidebar Task',
        'created_by' => $user->id,
    ]);

    foreach (range(1, 40) as $index) {
        $contact = Contact::factory()->create([
            'team_id' => $team->id,
            'name' => "Linked Sidebar Contact {$index}",
        ]);

        RecordLink::create([
            'team_id' => $team->id,
            'left_type' => $task->linkableType(),
            'left_id' => $task->id,
            'right_type' => $contact->linkableType(),
            'right_id' => $contact->id,
        ]);
    }

    TaskComment::factory()
        ->count(20)
        ->create([
            'team_id' => $team->id,
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit')
        ->resize(1600, 900)
        ->assertSee('Linked Records')
        ->assertSee('Linked Sidebar Contact 1')
        ->assertSee('Linked Sidebar Contact 40');

    $metrics = $page->script(<<<'JS'
        (() => {
            const sidebar = document.querySelector('[data-testid="editor-sidebar"]');

            if (!sidebar) {
                return null;
            }

            window.scrollTo(0, 500);
            sidebar.scrollTop = sidebar.scrollHeight;

            const style = window.getComputedStyle(sidebar);

            return {
                documentScrollable: document.documentElement.scrollHeight > window.innerHeight,
                overflowY: style.overflowY,
                position: style.position,
                clientHeight: sidebar.clientHeight,
                scrollHeight: sidebar.scrollHeight,
                scrollTop: sidebar.scrollTop,
                top: sidebar.getBoundingClientRect().top,
            };
        })()
    JS);

    expect($metrics)->not->toBeNull()
        ->and($metrics['documentScrollable'])->toBeTrue()
        ->and($metrics['position'])->toBe('sticky')
        ->and($metrics['overflowY'])->toBe('auto')
        ->and($metrics['scrollHeight'])->toBeGreaterThan($metrics['clientHeight'])
        ->and($metrics['scrollTop'])->toBeGreaterThan(0)
        ->and($metrics['top'])->toBeGreaterThanOrEqual(28)
        ->and($metrics['top'])->toBeLessThanOrEqual(36);

    $page->assertNoJavaScriptErrors();
});
