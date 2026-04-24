<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
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
        'due_at' => '2026-05-21',
    ]);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->where('project.id', $project->id)
            ->where('task.id', $task->id)
            ->where('task.creatorName', $user->name)
            ->where('task.dueAt', fn (?string $dueAt): bool => is_string($dueAt)
                && str_starts_with($dueAt, '2026-05-21'))
            ->where('task.updatedAt', fn (?string $updatedAt): bool => is_string($updatedAt)
                && str_contains($updatedAt, 'T'))
            ->where('task.commentsCount', 0)
            ->has('comments', 0)
            ->has('members')
            ->has('statuses', 6),
        );
});

test('task edit page includes existing comments', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);
    $olderComment = TaskComment::factory()->create([
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'First task note',
        'created_at' => now()->subMinute(),
    ]);
    $newerComment = TaskComment::factory()->create([
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'Latest task note',
        'created_at' => now(),
    ]);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->where('task.commentsCount', 2)
            ->has('comments', 2)
            ->where('comments.0.id', $newerComment->id)
            ->where('comments.0.body', 'Latest task note')
            ->where('comments.0.userId', $user->id)
            ->where('comments.0.userName', $user->name)
            ->where('comments.1.id', $olderComment->id)
            ->where('comments.1.body', 'First task note'),
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

test('task comments can be created from the task page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);

    $body = "Need API contract before frontend handoff.\n\n- confirm payload shape\n- confirm deadlines";

    actingAs($user)
        ->from(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->post(route('team.tasks.comments.store', ['current_team' => $team, 'task' => $task->id]), [
            'body' => $body,
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]));

    assertDatabaseHas('task_comments', [
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => $body,
    ]);
});

test('legacy serialized blocknote comment bodies remain readable', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);
    $body = json_encode([
        [
            'type' => 'paragraph',
            'content' => 'Need API contract before frontend handoff.',
        ],
    ]);

    actingAs($user)
        ->from(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->post(route('team.tasks.comments.store', ['current_team' => $team, 'task' => $task->id]), [
            'body' => $body,
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]));

    assertDatabaseHas('task_comments', [
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => $body,
    ]);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->where('task.commentsCount', 1)
            ->where('comments.0.body', $body),
        );
});

test('task comments cannot be created from another team route', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $task = Task::factory()->create();

    actingAs($user)
        ->post(route('team.tasks.comments.store', ['current_team' => $team, 'task' => $task->id]), [
            'body' => 'Cross-team comment',
        ])
        ->assertForbidden();

    expect(TaskComment::query()->count())->toBe(0);
});

test('task comments can be edited by their creator', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);
    $comment = TaskComment::factory()->create([
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'Original task note',
    ]);
    $body = "## Updated task note\n\n- finish copy\n- ship review";

    actingAs($user)
        ->from(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->patch(route('team.tasks.comments.update', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]), [
            'body' => $body,
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]));

    assertDatabaseHas('task_comments', [
        'id' => $comment->id,
        'body' => $body,
    ]);
});

test('task comments cannot be edited by another team member', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team = $owner->currentTeam;

    $team->members()->attach($editor, ['role' => 'member']);

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $owner->id,
    ]);
    $comment = TaskComment::factory()->create([
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $owner->id,
        'body' => 'Owner only',
    ]);

    actingAs($editor)
        ->patch(route('team.tasks.comments.update', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]), [
            'body' => 'Unauthorized edit',
        ])
        ->assertForbidden();

    assertDatabaseHas('task_comments', [
        'id' => $comment->id,
        'body' => 'Owner only',
    ]);
});

test('task comments can be deleted by their creator', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);
    $comment = TaskComment::factory()->create([
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'Remove me',
    ]);

    actingAs($user)
        ->from(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->delete(route('team.tasks.comments.destroy', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]))
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]));

    expect(TaskComment::query()->whereKey($comment->id)->exists())->toBeFalse();
});

test('task comments cannot be deleted by another team member', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team = $owner->currentTeam;

    $team->members()->attach($editor, ['role' => 'member']);

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $owner->id,
    ]);
    $comment = TaskComment::factory()->create([
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $owner->id,
        'body' => 'Still here',
    ]);

    actingAs($editor)
        ->delete(route('team.tasks.comments.destroy', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]))
        ->assertForbidden();

    assertDatabaseHas('task_comments', [
        'id' => $comment->id,
        'body' => 'Still here',
    ]);
});

test('tasks can be deleted from the task edit page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)
        ->delete(route('team.tasks.destroy', ['current_team' => $team, 'task' => $task->id]))
        ->assertRedirect(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]));

    expect(Task::query()->whereKey($task->id)->exists())->toBeFalse();
});

test('tasks cannot be deleted by non-team-members', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);

    $outsider = User::factory()->create();

    actingAs($outsider)
        ->delete(route('team.tasks.destroy', ['current_team' => $team, 'task' => $task->id]))
        ->assertForbidden();

    assertDatabaseHas('tasks', ['id' => $task->id]);
});

test('tasks cannot be deleted from another team route', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $otherProject = Project::factory()->create(['team_id' => $otherTeam->id]);
    $otherTask = Task::factory()->create([
        'team_id' => $otherTeam->id,
        'project_id' => $otherProject->id,
    ]);

    actingAs($user)
        ->delete(route('team.tasks.destroy', ['current_team' => $team, 'task' => $otherTask->id]))
        ->assertForbidden();

    assertDatabaseHas('tasks', ['id' => $otherTask->id]);
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

test('task due date patch persists due date and refreshes updated timestamp', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'due_at' => null,
    ]);

    $originalUpdatedAt = $task->updated_at;

    Carbon::setTestNow(now()->addMinute());

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            '_return_to_edit' => true,
            'due_at' => '2026-05-21',
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task]));

    Carbon::setTestNow();

    $task->refresh();

    expect($task->due_at?->format('Y-m-d'))->toBe('2026-05-21');
    expect($task->updated_at?->gt($originalUpdatedAt))->toBeTrue();
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
