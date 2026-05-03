<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    #[\Override]
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'date' => fake()->dateTimeBetween('-6 months', '+6 months')->format('Y-m-d'),
            'time' => fake()->optional()->time('H:i'),
        ];
    }
}
