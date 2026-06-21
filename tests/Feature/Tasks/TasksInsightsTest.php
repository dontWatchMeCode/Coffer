<?php

use App\Enums\InsightsTimeRange;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
});

it('redirects guests to the login page', function () {
    $this->get(route('team.tasks.insights', ['current_team' => $this->team]))
        ->assertRedirect(route('login'));
});

it('renders the tasks insights for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('team.tasks.insights', ['current_team' => $this->team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Insights')
            ->has('insights.kpis')
            ->has('insights.statusDistribution')
            ->has('insights.assignmentDistribution')
            ->has('insights.createdTrend')
            ->where('range', InsightsTimeRange::Last3Months->value)
            ->has('rangeOptions'));
});

it('computes completion rate, overdue, and open counts', function () {
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'title' => 'Planned',
        'status' => TaskStatus::Planned->value,
        'due_at' => now()->subDay(),
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'title' => 'In progress',
        'status' => TaskStatus::InProgress->value,
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'title' => 'Completed',
        'status' => TaskStatus::Completed->value,
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'title' => 'Dropped',
        'status' => TaskStatus::Dropped->value,
    ]);

    $this->actingAs($this->user)
        ->get(route('team.tasks.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.completionRate', 25)
            ->where('insights.kpis.overdue', 1)
            ->where('insights.kpis.totalOpen', 2));
});

it('scopes tasks to the current team', function () {
    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->create(['team_id' => $otherUser->currentTeam->id]);

    Task::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'project_id' => $otherProject->id,
        'created_by' => $otherUser->id,
        'status' => TaskStatus::Completed->value,
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::Completed->value,
    ]);

    $this->actingAs($this->user)
        ->get(route('team.tasks.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.completionRate', 100)
            ->where('insights.kpis.totalOpen', 0));
});

it('distributes tasks by status', function () {
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::Planned->value,
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::Planned->value,
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::InProgress->value,
    ]);

    $this->actingAs($this->user)
        ->get(route('team.tasks.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.statusDistribution.0.status', 'planned')
            ->where('insights.statusDistribution.0.count', 2)
            ->where('insights.statusDistribution.2.status', 'in_progress')
            ->where('insights.statusDistribution.2.count', 1)
            ->where('insights.statusDistribution.4.status', 'completed')
            ->where('insights.statusDistribution.4.count', 0));
});

it('distributes tasks by assignee', function () {
    $assignee = User::factory()->create();
    $this->team->members()->attach($assignee, ['role' => 'member']);

    Task::factory()->for($this->project)->assignedTo($assignee)->create();
    Task::factory()->for($this->project)->assignedTo($assignee)->create();
    Task::factory()->for($this->project)->create(['assigned_to' => null]);

    $this->actingAs($this->user)
        ->get(route('team.tasks.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.assignmentDistribution.0.assignee', $assignee->name)
            ->where('insights.assignmentDistribution.0.count', 2)
            ->where('insights.assignmentDistribution.1.assignee', 'Unassigned')
            ->where('insights.assignmentDistribution.1.count', 1));
});

it('filters tasks created within the selected range', function () {
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'title' => 'Recent',
        'status' => TaskStatus::Planned->value,
        'created_at' => now()->startOfMonth()->addDays(5),
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'title' => 'Old',
        'status' => TaskStatus::Planned->value,
        'created_at' => now()->subYear(),
    ]);

    $this->actingAs($this->user)
        ->get(route('team.tasks.insights', ['current_team' => $this->team, 'range' => InsightsTimeRange::ThisMonth->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('range', InsightsTimeRange::ThisMonth->value)
            ->has('insights.createdTrend', 1)
            ->where('insights.createdTrend.0.created', 1));
});

it('returns 404 when the tasks feature is disabled', function () {
    $this->team->forceFill(['feature_settings' => ['tasks' => false]])->save();

    $this->actingAs($this->user)
        ->get(route('team.tasks.insights', ['current_team' => $this->team]))
        ->assertNotFound();
});
