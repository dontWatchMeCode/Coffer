<?php

use App\Models\Bookmark;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('search page requires authentication', function () {
    $team = Team::factory()->create();

    get(route('team.search.page', ['current_team' => $team]))
        ->assertRedirect(route('login'));
});

test('search page requires team membership', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $otherTeam]))
        ->assertForbidden();
});

test('search page renders with empty query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('query', '')
            ->where('type', '')
            ->where('tag', '')
            ->where('results', [])
            ->has('tags')
            ->has('types')
        );
});

test('search page returns results for a query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Important task',
    ]);

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Important person',
    ]);

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team, 'q' => 'Important']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('query', 'Important')
            ->has('results.tasks')
            ->has('results.contacts')
        );
});

test('search page does not return records from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Task::factory()->create([
        'team_id' => $otherTeam->id,
        'project_id' => Project::factory()->create(['team_id' => $otherTeam->id]),
        'title' => 'Secret task',
    ]);

    Bookmark::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Secret bookmark',
    ]);

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team, 'q' => 'Secret']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('results.tasks', [])
            ->where('results.bookmarks', [])
        );
});

test('search page returns up to 50 results per category', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    Task::factory()->count(55)->create([
        'team_id' => $team->id,
        'project_id' => $project,
        'title' => 'Batch task',
    ]);

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team, 'q' => 'Batch']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('query', 'Batch')
            ->has('results.tasks', 50)
        );
});

test('search page filters by type', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Meeting notes',
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Meeting task',
    ]);

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team, 'q' => 'Meeting', 'type' => 'notes']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('type', 'notes')
            ->has('results.notes')
            ->where('results.tasks', [])
        );
});

test('search page filters by tag', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Urgent', 'slug' => 'urgent']);

    $taggedTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Tagged task',
    ]);
    $taggedTask->recordTags()->attach($tag);

    $untaggedTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Other task',
    ]);

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team, 'q' => 'task', 'tag' => 'urgent']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('tag', 'urgent')
            ->has('results.tasks', 1)
        );
});

test('search page passes team tags', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Tag::factory()->create(['team_id' => $team->id, 'name' => 'Work', 'slug' => 'work']);
    Tag::factory()->create(['team_id' => $team->id, 'name' => 'Personal', 'slug' => 'personal']);

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('tags', 2)
        );
});

test('search page does not pass tags from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Tag::factory()->create(['team_id' => $otherTeam->id, 'name' => 'Secret', 'slug' => 'secret']);

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('tags', 0)
        );
});

test('search page browses all records by type without text query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Task one',
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Task two',
    ]);

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'A person',
    ]);

    actingAs($user)
        ->get(route('team.search.page', ['current_team' => $team, 'type' => 'tasks']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('type', 'tasks')
            ->has('results.tasks', 2)
            ->where('results.contacts', [])
        );
});
