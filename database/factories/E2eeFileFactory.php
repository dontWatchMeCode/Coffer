<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\E2eeFile;
use App\Models\E2eeKeyEpoch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<E2eeFile>
 */
class E2eeFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'file_id' => fake()->unique()->numberBetween(1, 100000),
            'epoch_id' => E2eeKeyEpoch::factory(),
            'version' => 1,
            'disk' => 'local',
            'path' => 'vault/'.fake()->uuid(),
            'size' => fake()->numberBetween(1, 100000),
            'chunk_count' => '1',
            'is_tombstone' => false,
        ];
    }
}
