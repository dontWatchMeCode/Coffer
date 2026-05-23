<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\QueryException;

use function Pest\Laravel\actingAs;

test('project task and comment relationships are defined', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->current_team_id]);
    $task = Task::factory()->create([
        'team_id' => $project->team_id,
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);
    $comment = TaskComment::factory()->create([
        'team_id' => $task->team_id,
        'task_id' => $task->id,
        'user_id' => $user->id,
    ]);

    actingAs($user);
    $project->refresh();
    $task->refresh();
    $comment->refresh();

    expect($project->tasks)->toHaveCount(1)
        ->and($project->tasks->first()->is($task))->toBeTrue()
        ->and($task->project->is($project))->toBeTrue()
        ->and($task->comments->first()->is($comment))->toBeTrue()
        ->and($comment->task->is($task))->toBeTrue();
});

test('task status is cast and defaults are applied', function () {
    $task = Task::factory()->create();

    expect($task->status)->toBe(TaskStatus::Planned)
        ->and($task->progress)->toBe(0)
        ->and($task->position)->toBe(0)
        ->and($task->completed_at)->toBeNull();
});

test('projects are unique by team and name', function () {
    $team = Team::factory()->create();

    Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Operations',
    ]);

    expect(fn () => Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Operations',
    ]))->toThrow(QueryException::class);
});

test('tasks may be unassigned', function () {
    $task = Task::factory()->create([
        'assigned_to' => null,
    ]);

    expect($task->assignee)->toBeNull();
});

test('projects are scoped to the authenticated users current team', function () {
    $user = User::factory()->create();
    $secondaryTeam = Team::factory()->create();

    $secondaryTeam->members()->attach($user, ['role' => 'member']);
    $user->switchTeam($secondaryTeam);

    $visibleProject = Project::factory()->create(['team_id' => $secondaryTeam->id]);
    Project::factory()->create();

    actingAs($user);

    expect(Project::pluck('id')->all())->toBe([$visibleProject->id]);
});

test('team id is filled from the authenticated users current team', function () {
    $user = User::factory()->create();

    actingAs($user);

    $project = Project::create([
        'name' => 'Roadmap',
    ]);

    expect($project->team_id)->toBe($user->current_team_id);
});

test('team scoped records require an explicit team when unauthenticated', function () {
    expect(fn () => Project::create([
        'name' => 'Roadmap',
    ]))->toThrow(LogicException::class);
});

test('team scoped records require a current team to query', function () {
    Project::factory()->create();

    expect(fn () => Project::query()->get())->toThrow(LogicException::class);
});

test('team scoped records must still match the current team when updating', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $otherTeam->members()->attach($user, ['role' => 'member']);

    $project = Project::factory()->create(['team_id' => $user->current_team_id]);

    $user->switchTeam($otherTeam);
    actingAs($user);

    $project = Project::withoutGlobalScopes()->findOrFail($project->id);

    expect(fn () => $project->update([
        'description' => 'Cross-team update',
    ]))->toThrow(LogicException::class);
});

test('tasks must belong to the same team as their project', function () {
    $project = Project::factory()->create();
    $otherTeam = Team::factory()->create();

    expect(fn () => Task::create([
        'team_id' => $otherTeam->id,
        'project_id' => $project->id,
        'created_by' => User::factory()->create()->id,
        'title' => 'Cross-team task',
        'status' => TaskStatus::Planned,
        'progress' => 0,
        'position' => 0,
    ]))->toThrow(LogicException::class);
});

test('task creators must belong to the task team', function () {
    $project = Project::factory()->create();
    $outsider = User::factory()->create();

    expect(fn () => Task::create([
        'team_id' => $project->team_id,
        'project_id' => $project->id,
        'created_by' => $outsider->id,
        'title' => 'External creator',
        'status' => TaskStatus::Planned,
        'progress' => 0,
        'position' => 0,
    ]))->toThrow(LogicException::class);
});

test('comment authors must belong to the task team', function () {
    $task = Task::factory()->create();
    $outsider = User::factory()->create();

    expect(fn () => TaskComment::create([
        'team_id' => $task->team_id,
        'task_id' => $task->id,
        'user_id' => $outsider->id,
    ]))->toThrow(LogicException::class);
});

test('assignedTo factory keeps the assignee on the project team', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $task = Task::factory()
        ->for($project)
        ->assignedTo($user)
        ->create();

    expect($task->team_id)->toBe($project->team_id)
        ->and($user->belongsToTeamId((int) $project->team_id))->toBeTrue();
});

test('deleting a project cascades to its tasks and comments', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'team_id' => $project->team_id,
        'project_id' => $project->id,
    ]);
    $comment = TaskComment::factory()->create([
        'team_id' => $task->team_id,
        'task_id' => $task->id,
    ]);

    $project->delete();

    expect($task->fresh())->toBeNull()
        ->and($comment->fresh())->toBeNull();
});
