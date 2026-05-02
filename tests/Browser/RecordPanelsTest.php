<?php

use App\Models\Contact;
use App\Models\Project;
use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;

it('task edit page shows linked records and tags panels', function () {
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
    $tag = Tag::factory()->create([
        'team_id' => $team->id,
        'name' => 'Important',
        'slug' => 'important',
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

    visit('/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit')
        ->assertSee('Linked Records')
        ->assertSee('Panel Contact')
        ->assertSee('Tags')
        ->assertSee('Important')
        ->assertNoJavaScriptErrors();
});

it('record link panel searches for link candidates', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'title' => 'Link Candidate Task',
    ]);
    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Linkable Browser Contact',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit')
        ->fill('[data-testid="record-link-search-input"]', 'Linkable Browser');

    waitForBrowserText($page, 'Linkable Browser Contact')
        ->assertNoJavaScriptErrors();
});

it('record tag panel searches for existing tags', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'title' => 'Tag Candidate Task',
    ]);
    Tag::factory()->create([
        'team_id' => $team->id,
        'name' => 'Existing Browser Tag',
        'slug' => 'existing-browser-tag',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit')
        ->fill('[data-testid="record-tag-search-input"]', 'Existing Browser');

    waitForBrowserText($page, 'Existing Browser Tag')
        ->assertNoJavaScriptErrors();
});
