<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'team_id' => fn (array $attributes): int => Project::withoutGlobalScopes()->findOrFail($attributes['project_id'])->team_id,
            'assigned_to' => null,
            'created_by' => function (array $attributes): int {
                $team = Team::findOrFail($attributes['team_id']);
                $user = User::factory()->create();

                if (! $user->belongsToTeam($team)) {
                    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
                }

                return $user->id;
            },
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'status' => TaskStatus::Planned->value,
            'progress' => 0,
            'time_estimate' => null,
            'position' => 0,
            'due_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Assign the task to a user.
     */
    public function assignedTo(User $user): static
    {
        return $this
            ->state(fn (): array => [
                'assigned_to' => $user->id,
            ])
            ->afterMaking(fn (Task $task): null => $this->attachAssigneeToProjectTeam($task, $user))
            ->afterCreating(fn (Task $task): null => $this->attachAssigneeToProjectTeam($task, $user));
    }

    /**
     * Ensure the assigned user belongs to the task project team.
     */
    protected function attachAssigneeToProjectTeam(Task $task, User $user): null
    {
        $teamId = Project::withoutGlobalScopes()->findOrFail($task->project_id)->team_id;

        if (! $user->belongsToTeamId((int) $teamId)) {
            Team::findOrFail($teamId)->members()->attach($user, ['role' => TeamRole::Member->value]);
        }

        $task->team_id = $teamId;

        return null;
    }
}
