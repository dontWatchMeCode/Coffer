<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FileItem;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileItem>
 */
class FileItemFactory extends Factory
{
    #[\Override]
    protected $model = FileItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional(0.5)->sentence(),
            'disk' => 'local',
            'path' => 'files/'.fake()->uuid().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(20_000, 2_000_000),
            'width' => fake()->numberBetween(640, 2400),
            'height' => fake()->numberBetween(480, 1800),
        ];
    }
}
