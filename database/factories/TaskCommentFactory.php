<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskComment>
 */
class TaskCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'team_id' => fn (array $attributes): int => Task::withoutGlobalScopes()->findOrFail($attributes['task_id'])->team_id,
            'user_id' => function (array $attributes): int {
                $team = Team::findOrFail($attributes['team_id']);
                $user = User::factory()->create();

                if (! $user->belongsToTeamId((int) $team->id)) {
                    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
                }

                return $user->id;
            },
            'body' => fake()->paragraph(),
        ];
    }
}
