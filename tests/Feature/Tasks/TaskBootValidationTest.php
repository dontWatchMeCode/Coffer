<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

it('prevents saving a task with a non-existent project', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team, ['role' => 'admin']);
    $user->switchTeam($team);

    expect(fn () => Task::create([
        'team_id' => $team->id,
        'project_id' => 9999,
        'created_by' => $user->id,
        'title' => 'Test',
        'status' => 'planned',
    ]))->toThrow(LogicException::class, 'The selected project does not exist.');
});

it('allows saving a task when project and team match', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team, ['role' => 'admin']);
    $user->switchTeam($team);

    $project = Project::factory()->create(['team_id' => $team->id]);

    $task = Task::create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'title' => 'Test',
        'status' => 'planned',
    ]);

    expect($task)->toBeInstanceOf(Task::class);
    expect($task->fresh())->not->toBeNull();
});
