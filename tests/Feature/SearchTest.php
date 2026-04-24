<?php

use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('search requires authentication', function () {
    $team = Team::factory()->create();

    get(route('team.search', ['current_team' => $team, 'q' => 'test']))
        ->assertRedirect(route('login'));
});

test('search returns matching team records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull();

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Special task title',
        'description' => 'Task description',
    ]);

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Special contact name',
    ]);

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Special event title',
    ]);

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Special project name',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'Special']))
        ->assertOk()
        ->assertJsonPath('tasks.0.title', 'Special task title')
        ->assertJsonPath('contacts.0.title', 'Special contact name')
        ->assertJsonPath('events.0.title', 'Special event title')
        ->assertJsonPath('projects.0.title', 'Special project name');
});

test('search does not return records from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Task::factory()->create([
        'team_id' => $otherTeam->id,
        'project_id' => Project::factory()->create(['team_id' => $otherTeam->id]),
        'title' => 'Secret task',
    ]);

    Contact::factory()->create([
        'team_id' => $otherTeam->id,
        'name' => 'Secret contact',
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Secret event',
    ]);

    Project::factory()->create([
        'team_id' => $otherTeam->id,
        'name' => 'Secret project',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'Secret']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects');
});

test('search returns empty results for blank query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => '']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects');
});

test('search requires team membership', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $otherTeam, 'q' => 'test']))
        ->assertForbidden();
});
