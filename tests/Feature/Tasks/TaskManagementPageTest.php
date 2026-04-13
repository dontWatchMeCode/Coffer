<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

test('team task page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull();

    Project::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->get(route('team.tasks.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Index')
            ->has('projects', 1)
            ->has('stats'),
        );
});

test('task page stats treat dropped tasks as closed work', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull();

    $project = Project::factory()->create(['team_id' => $team->id]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Planned,
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Completed,
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Dropped,
    ]);

    actingAs($user)
        ->get(route('team.tasks.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Index')
            ->where('stats.openTaskCount', 1)
            ->where('stats.closedTaskCount', 2)
            ->where('projects.0.openTasksCount', 1)
            ->where('projects.0.closedTasksCount', 2),
        );
});

test('team project detail page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->where('project.id', $project->id)
            ->has('tasks', 1)
            ->has('members')
            ->has('statuses', 6),
        );
});

test('task payload serializes completed timestamp as iso 8601', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Completed,
        'completed_at' => now(),
    ]);

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->where('tasks.0.id', $task->id)
            ->where('tasks.0.completedAt', fn (?string $completedAt): bool => is_string($completedAt)
                && str_contains($completedAt, 'T')
                && (str_ends_with($completedAt, 'Z') || str_contains($completedAt, '+00:00'))),
        );
});

test('task edit page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->where('project.id', $project->id)
            ->where('task.id', $task->id)
            ->where('task.commentsCount', 0)
            ->has('members')
            ->has('statuses', 6),
        );
});

test('non team members cannot view the team task index page', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $outsider = User::factory()->create();

    actingAs($outsider)
        ->get(route('team.tasks.index', ['current_team' => $team]))
        ->assertForbidden();
});

test('non team members cannot view the team project detail page', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $outsider = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);

    actingAs($outsider)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->assertForbidden();
});

test('non team members cannot view the task edit page', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $outsider = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $owner->id,
    ]);

    actingAs($outsider)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertForbidden();
});

test('duplicate project names are rejected on store', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    Project::factory()->create(['team_id' => $team->id, 'name' => 'Client Portal']);

    actingAs($user)
        ->from(route('team.tasks.index', ['current_team' => $team]))
        ->post(route('team.tasks.projects.store', ['current_team' => $team]), [
            'name' => 'Client Portal',
            'description' => 'Duplicate',
            'archived' => false,
        ])
        ->assertSessionHasErrors(['name']);
});

test('projects can be created from the team tasks page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = actingAs($user)
        ->post(route('team.tasks.projects.store', ['current_team' => $team]), [
            'name' => 'Client Portal',
            'description' => 'Delivery work',
            'archived' => false,
        ]);

    $response->assertRedirect(route('team.tasks.index', ['current_team' => $team]));

    assertDatabaseHas('projects', [
        'team_id' => $team->id,
        'name' => 'Client Portal',
    ]);
});

test('projects can be updated from the team tasks page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id, 'name' => 'Client Portal']);

    $response = actingAs($user)
        ->patch(route('team.tasks.projects.update', ['current_team' => $team, 'project' => $project->id]), [
            'name' => 'Client Portal v2',
            'description' => 'Updated scope',
            'archived' => true,
        ]);

    $response->assertRedirect(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]));

    assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Client Portal v2',
        'archived' => true,
    ]);
});

test('project partial patch keeps archived state when archived is omitted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Client Portal',
        'archived' => true,
    ]);

    actingAs($user)
        ->patch(route('team.tasks.projects.update', ['current_team' => $team, 'project' => $project->id]), [
            'name' => 'Client Portal v2',
        ])
        ->assertRedirect(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]));

    assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Client Portal v2',
        'archived' => true,
    ]);
});

test('projects cannot be updated from another team route', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherProject = Project::factory()->create();

    actingAs($user)
        ->patch(route('team.tasks.projects.update', ['current_team' => $team, 'project' => $otherProject->id]), [
            'name' => 'Unauthorized update',
        ])
        ->assertForbidden();

    assertDatabaseHas('projects', [
        'id' => $otherProject->id,
        'name' => $otherProject->name,
    ]);
});

test('project names must be unique within a team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    Project::factory()->create(['team_id' => $team->id, 'name' => 'Client Portal']);
    $project = Project::factory()->create(['team_id' => $team->id, 'name' => 'Legacy Project']);

    actingAs($user)
        ->from(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->patch(route('team.tasks.projects.update', ['current_team' => $team, 'project' => $project->id]), [
            'name' => 'Client Portal',
            'description' => 'Updated scope',
            'archived' => false,
        ])
        ->assertSessionHasErrors(['name']);
});

test('tasks cannot be created with a project from another team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $otherProject = Project::factory()->create();

    actingAs($user)
        ->from(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->post(route('team.tasks.store', ['current_team' => $team]), [
            'project_id' => $otherProject->id,
            'assigned_to' => '',
            'title' => 'Cross team task',
            'description' => 'Should fail',
            'status' => TaskStatus::Planned->value,
            'progress' => 0,
            'position' => 0,
        ])
        ->assertSessionHasErrors(['project_id']);
});

test('tasks can be created from the team tasks page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    $response = actingAs($user)
        ->post(route('team.tasks.store', ['current_team' => $team]), [
            'project_id' => $project->id,
            'assigned_to' => '',
            'title' => 'Plan launch checklist',
            'description' => 'Initial delivery plan',
            'status' => TaskStatus::Planned->value,
            'progress' => 0,
            'position' => 0,
        ]);

    $response->assertRedirect(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]));

    assertDatabaseHas('tasks', [
        'team_id' => $team->id,
        'project_id' => $project->id,
        'title' => 'Plan launch checklist',
        'created_by' => $user->id,
    ]);
});

test('tasks can be updated from the team tasks page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Planned,
    ]);

    $response = actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            'project_id' => $project->id,
            '_return_to_edit' => true,
            'assigned_to' => '',
            'title' => 'Ship launch checklist',
            'description' => 'Updated delivery plan',
            'status' => TaskStatus::Completed->value,
            'progress' => 100,
            'position' => 2,
        ]);

    $response->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task]));

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Ship launch checklist',
        'status' => TaskStatus::Completed->value,
        'progress' => 100,
        'position' => 2,
    ]);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('tasks cannot be updated from another team route', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTask = Task::factory()->create();

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $otherTask->id]), [
            'status' => TaskStatus::Completed->value,
        ])
        ->assertForbidden();

    assertDatabaseHas('tasks', [
        'id' => $otherTask->id,
        'status' => TaskStatus::Planned->value,
    ]);
});

test('task status can be updated with a partial patch payload', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'title' => 'Plan launch checklist',
        'description' => 'Initial delivery plan',
        'status' => TaskStatus::Planned,
        'progress' => 15,
        'position' => 3,
    ]);

    $response = actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            '_return_to_edit' => true,
            'status' => TaskStatus::Completed->value,
        ]);

    $response->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task]));

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Plan launch checklist',
        'description' => 'Initial delivery plan',
        'status' => TaskStatus::Completed->value,
        'progress' => 15,
        'position' => 3,
    ]);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('task completion timestamp is cleared when status changes away from completed', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Completed,
        'completed_at' => now(),
    ]);

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            '_return_to_edit' => true,
            'status' => TaskStatus::Planned->value,
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task]));

    expect($task->fresh()->completed_at)->toBeNull();
});

test('task partial patch validates submitted fields', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'progress' => 20,
    ]);

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            '_return_to_edit' => true,
            'progress' => 110,
        ])
        ->assertSessionHasErrors(['progress']);

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'progress' => 20,
    ]);
});

test('task update from list view redirects back to project page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Planned,
    ]);

    actingAs($user)
        ->from(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            'status' => TaskStatus::Completed->value,
        ])
        ->assertRedirect(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]));

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);
});

test('multi team members can view another team project routes after switching context', function () {
    $user = User::factory()->create();
    $primaryTeam = $user->currentTeam;
    $secondaryTeam = Team::factory()->create();

    expect($primaryTeam)->not->toBeNull();

    $secondaryTeam->members()->attach($user, ['role' => 'member']);

    $project = Project::factory()->create(['team_id' => $secondaryTeam->id]);
    $task = Task::factory()->create([
        'team_id' => $secondaryTeam->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $secondaryTeam, 'project' => $project->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->where('project.id', $project->id)
            ->has('tasks', 1),
        );

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $secondaryTeam, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->where('project.id', $project->id)
            ->where('task.id', $task->id),
        );
});
