<?php

use App\Enums\TaskStatus;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    expect($team)->not->toBeNull();

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('dashboard.stats')
            ->has('dashboard.today')
            ->has('dashboard.recent')
            ->missing('dashboard.quickActions')
            ->missing('dashboard.mcp'));
});

test('dashboard summarizes current team work', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull();

    $project = Project::factory()->create(['team_id' => $team->id, 'name' => 'Launch']);
    $otherUser = User::factory()->create();
    $otherTeam = $otherUser->currentTeam;
    $otherProject = Project::factory()->create(['team_id' => $otherTeam->id]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'title' => 'Ship dashboard',
        'status' => TaskStatus::InProgress,
        'due_at' => now()->subDay(),
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'title' => 'Review notes',
        'status' => TaskStatus::Planned,
        'due_at' => now(),
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Completed,
        'due_at' => now(),
    ]);

    Task::factory()->create([
        'team_id' => $otherTeam->id,
        'project_id' => $otherProject->id,
        'status' => TaskStatus::Planned,
        'due_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Standup',
        'date' => now()->toDateString(),
        'time' => '10:00',
    ]);

    $this
        ->actingAs($user)
        ->get(route('team.dashboard', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('dashboard.stats.openTasks', 2)
            ->where('dashboard.stats.dueToday', 1)
            ->where('dashboard.stats.overdue', 1)
            ->where('dashboard.stats.eventsToday', 1)
            ->where('dashboard.stats.activeProjects', 1)
            ->has('dashboard.today.tasks', 2)
            ->where('dashboard.today.tasks.0.title', 'Ship dashboard')
            ->has('dashboard.today.events', 1)
            ->where('dashboard.today.events.0.title', 'Standup'));
});
