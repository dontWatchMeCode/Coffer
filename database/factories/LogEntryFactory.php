<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LogEntry;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogEntry>
 */
class LogEntryFactory extends Factory
{
    #[\Override]
    protected $model = LogEntry::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'body' => fake()->paragraph(),
            'category' => fake()->optional()->word(),
        ];
    }
}
