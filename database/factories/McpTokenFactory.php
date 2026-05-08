<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\McpToken;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<McpToken>
 */
class McpTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $plainTextToken = 'mcp_test_'.fake()->sha256();

        return [
            'user_id' => $user->id,
            'team_id' => $user->current_team_id ?? Team::factory(),
            'name' => fake()->words(2, true),
            'token_hash' => McpToken::hashToken($plainTextToken),
            'token' => $plainTextToken,
            'abilities' => [
                'collections' => 'write',
                'notes' => 'write',
                'bookmarks' => 'write',
                'contacts' => 'write',
                'calendar' => 'write',
                'tasks' => 'write',
                'task_projects' => ['mode' => 'all', 'ids' => []],
            ],
        ];
    }
}
