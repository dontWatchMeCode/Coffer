<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('project name and description changes are logged', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Old Name',
        'description' => 'Old description',
    ]);

    actingAs($user)
        ->patch(route('team.tasks.projects.update', ['current_team' => $team, 'project' => $project->id]), [
            'name' => 'New Name',
            'description' => 'New description',
        ]);

    $activities = $project->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(2)
        ->and($activities->first()->event)->toBe('updated');
});

test('project archived changes are not logged', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Client Portal',
        'archived' => false,
    ]);

    actingAs($user)
        ->patch(route('team.tasks.projects.update', ['current_team' => $team, 'project' => $project->id]), [
            'name' => 'Client Portal',
            'archived' => true,
        ]);

    $activities = $project->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(1)
        ->and($activities->first()->event)->toBe('created');
});

test('task field changes are logged', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'title' => 'Old Title',
        'status' => TaskStatus::Planned,
    ]);

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            'project_id' => $project->id,
            'title' => 'New Title',
            'status' => TaskStatus::Completed->value,
        ]);

    $activities = $task->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(2)
        ->and($activities->first()->event)->toBe('updated');
});

test('task completed_at is not logged separately', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'status' => TaskStatus::Planned,
        'completed_at' => null,
    ]);

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            'project_id' => $project->id,
            'status' => TaskStatus::Completed->value,
        ]);

    $activities = $task->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(2)
        ->and($activities->first()->event)->toBe('updated');

    $changes = $activities->first()->attribute_changes?->toArray() ?? [];

    expect(array_keys($changes['attributes'] ?? []))->not->toContain('completed_at');
});

test('task completed_at null is not logged when reopening', function () {
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
            'project_id' => $project->id,
            'status' => TaskStatus::Planned->value,
        ]);

    $activities = $task->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(2)
        ->and($activities->first()->event)->toBe('updated');

    $changes = $activities->first()->attribute_changes?->toArray() ?? [];

    expect(array_keys($changes['attributes'] ?? []))->not->toContain('completed_at');
});

test('comment body changes are logged', function () {
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
        'body' => 'Original body',
    ]);

    actingAs($user)
        ->patch(route('team.tasks.comments.update', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]), [
            'body' => 'Updated body',
        ]);

    $activities = $comment->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(2)
        ->and($activities->first()->event)->toBe('updated');
});

test('project show page includes activity history', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Old Name',
    ]);

    actingAs($user)
        ->patch(route('team.tasks.projects.update', ['current_team' => $team, 'project' => $project->id]), [
            'name' => 'New Name',
        ]);

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->has('activityHistory')
            ->where('activityHistory.subject_type', 'project')
            ->where('activityHistory.subject_id', $project->id)
            ->whereType('activityHistory.total', 'integer'));
});

test('task edit page includes activity history', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'title' => 'Old Title',
    ]);

    actingAs($user)
        ->patch(route('team.tasks.update', ['current_team' => $team, 'task' => $task->id]), [
            'project_id' => $project->id,
            'title' => 'New Title',
        ]);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->has('activityHistory')
            ->where('activityHistory.subject_type', 'task')
            ->where('activityHistory.subject_id', $task->id)
            ->whereType('activityHistory.total', 'integer'));
});

test('task edit page includes comment activity history', function () {
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
        'body' => 'Original body',
    ]);

    actingAs($user)
        ->patch(route('team.tasks.comments.update', ['current_team' => $team, 'task' => $task->id, 'comment' => $comment->id]), [
            'body' => 'Updated body',
        ]);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Edit')
            ->has('comments', 1)
            ->has('comments.0.activityHistory', 2)
            ->where('comments.0.activityHistory.0.event', 'updated')
            ->where('comments.0.activityHistory.0.causerName', $user->name)
            ->has('comments.0.activityHistory.0.changedFields'));
});
