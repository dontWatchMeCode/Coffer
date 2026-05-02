<?php

use App\Models\Contact;
use App\Models\Project;
use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\Task;
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
