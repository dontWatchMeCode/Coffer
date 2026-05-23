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
            ->has('tasks.data', 1)
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
            ->where('tasks.data.0.id', $task->id)
            ->where('tasks.data.0.completedAt', fn (?string $completedAt): bool => is_string($completedAt)
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
        'created_at' => now()->subMinute(),
    ]);
    $olderComment->syncBlocks(taskCommentBlocks('First task note'));
    $newerComment = TaskComment::factory()->create([
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
        'source' => 'mcp',
        'mcp_token_name' => 'Claude Desktop',
        'created_at' => now(),
    ]);
    $newerComment->syncBlocks(taskCommentBlocks('Latest task note'));

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->where('task.commentsCount', 2)
            ->has('comments', 2)
            ->where('comments.0.id', $newerComment->id)
            ->where('comments.0.blocks.0.payload.content', 'Latest task note')
            ->where('comments.0.userId', $user->id)
            ->where('comments.0.userName', $user->name)
            ->where('comments.0.source', 'mcp')
            ->where('comments.0.mcpTokenName', 'Claude Desktop')
            ->where('comments.1.id', $olderComment->id)
            ->where('comments.1.source', 'user')
            ->where('comments.1.blocks.0.payload.content', 'First task note'),
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
        ->assertNotFound();

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

test('task creation defaults to planned status when status is omitted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    $response = actingAs($user)
        ->post(route('team.tasks.store', ['current_team' => $team]), [
            'project_id' => $project->id,
            'title' => 'Quick task',
        ]);

    $task = Task::query()
        ->where('team_id', $team->id)
        ->where('title', 'Quick task')
        ->first();

    expect($task)->not->toBeNull();
    expect($task->status)->toBe(TaskStatus::Planned);

    $response->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task, 'edit' => 'description']));
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

    $task = Task::query()
        ->where('team_id', $team->id)
        ->where('project_id', $project->id)
        ->where('title', 'Plan launch checklist')
        ->first();

    expect($task)->not->toBeNull();

    $response->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task, 'edit' => 'description']));

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
            'blocks' => taskCommentBlocks($body),
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]));

    assertDatabaseHas('task_comments', [
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => null,
    ]);

    assertDatabaseHas('rte_blocks', [
        'blockable_type' => TaskComment::class,
        'type' => 'text',
        'payload' => json_encode(['content' => $body]),
    ]);
});

test('task comments expose block payloads on the task edit page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);
    $body = 'Need API contract before frontend handoff.';
    $comment = TaskComment::factory()->create([
        'team_id' => $team->id,
        'task_id' => $task->id,
        'user_id' => $user->id,
    ]);
    $comment->syncBlocks(taskCommentBlocks($body));

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->where('task.commentsCount', 1)
            ->where('comments.0.blocks.0.payload.content', $body),
        );
});

test('task comments cannot be created from another team route', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $task = Task::factory()->create();

    actingAs($user)
        ->post(route('team.tasks.comments.store', ['current_team' => $team, 'task' => $task->id]), [
            'blocks' => taskCommentBlocks('Cross-team comment'),
        ])
        ->assertNotFound();

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
    ]);
    $comment->syncBlocks(taskCommentBlocks('Original task note'));
    $body = "## Updated task note\n\n- finish copy\n- ship review";

    actingAs($user)
        ->from(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->patch(route('team.tasks.comments.update', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]), [
            'blocks' => taskCommentBlocks($body),
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]));

    assertDatabaseHas('rte_blocks', [
        'blockable_type' => TaskComment::class,
        'blockable_id' => $comment->id,
        'payload' => json_encode(['content' => $body]),
    ]);
});

test('task comment edits update existing blocks with json payloads', function () {
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
    ]);
    $block = $comment->blocks()->firstOrFail();

    actingAs($user)
        ->from(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->patch(route('team.tasks.comments.update', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]), [
            'blocks' => [[
                'id' => $block->id,
                'type' => 'text',
                'position' => 0,
                'payload' => ['content' => 'Updated existing block'],
            ]],
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]));

    expect($block->fresh()->payload)->toBe(['content' => 'Updated existing block']);
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
    ]);
    $comment->syncBlocks(taskCommentBlocks('Owner only'));

    actingAs($editor)
        ->patch(route('team.tasks.comments.update', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]), [
            'blocks' => taskCommentBlocks('Unauthorized edit'),
        ])
        ->assertNotFound();

    assertDatabaseHas('rte_blocks', [
        'blockable_type' => TaskComment::class,
        'blockable_id' => $comment->id,
        'payload' => json_encode(['content' => 'Owner only']),
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
    ]);
    $comment->syncBlocks(taskCommentBlocks('Remove me'));

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
    ]);
    $comment->syncBlocks(taskCommentBlocks('Still here'));

    actingAs($editor)
        ->delete(route('team.tasks.comments.destroy', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]))
        ->assertNotFound();

    assertDatabaseHas('rte_blocks', [
        'blockable_type' => TaskComment::class,
        'blockable_id' => $comment->id,
        'payload' => json_encode(['content' => 'Still here']),
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

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
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
        ->assertNotFound();

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
        ->assertNotFound();

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

test('task time estimate patch persists and appears in payload', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'time_estimate' => null,
    ]);

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            '_return_to_edit' => true,
            'time_estimate' => 150,
        ])
        ->assertRedirect(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task]));

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'time_estimate' => 150,
    ]);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->where('task.timeEstimate', 150),
        );
});

test('task time estimate can be cleared by sending null', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'time_estimate' => 120,
    ]);

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            '_return_to_edit' => true,
            'time_estimate' => null,
        ]);

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'time_estimate' => null,
    ]);
});

test('task time estimate rejects negative values', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'time_estimate' => 60,
    ]);

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            '_return_to_edit' => true,
            'time_estimate' => -5,
        ])
        ->assertSessionHasErrors(['time_estimate']);

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'time_estimate' => 60,
    ]);
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
            ->has('tasks.data', 1),
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
