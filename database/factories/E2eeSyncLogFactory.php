<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\E2eeSyncLog;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<E2eeSyncLog>
 */
class E2eeSyncLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'change_type' => 'record',
            'is_tombstone' => false,
            'created_at' => now(),
        ];
    }
}
