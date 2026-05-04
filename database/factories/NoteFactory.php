<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Note;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    #[\Override]
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->optional(0.8)->paragraph(),
            'format' => 'text',
        ];
    }
}
