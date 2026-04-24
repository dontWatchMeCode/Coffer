<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
{
    protected $model = Bookmark::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->sentence(3),
            'url' => fake()->url(),
            'description' => fake()->optional(0.6)->sentence(),
            'tags' => fake()->optional(0.5)->randomElement([
                ['reference', 'docs'],
                ['design', 'inspiration'],
                ['tools', 'dev'],
                ['news'],
                null,
            ]),
            'notes' => fake()->optional(0.4)->paragraph(),
            'is_archived' => fake()->boolean(15),
        ];
    }
}
