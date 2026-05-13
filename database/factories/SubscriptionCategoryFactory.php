<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SubscriptionCategory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionCategory>
 */
class SubscriptionCategoryFactory extends Factory
{
    #[\Override]
    protected $model = SubscriptionCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Entertainment', 'Productivity', 'Music', 'Cloud Storage', 'Development', 'News', 'Health', 'Education', 'Fitness', 'Finance']);

        return [
            'team_id' => Team::factory(),
            'name' => $name,
            'slug' => SubscriptionCategory::slugFor($name),
        ];
    }
}
