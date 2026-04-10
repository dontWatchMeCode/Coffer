<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
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
        ->assertNotFound();
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
        ->assertNotFound();
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
        ->from(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            'progress' => 110,
        ])
        ->assertSessionHasErrors(['progress']);

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'progress' => 20,
    ]);
});
