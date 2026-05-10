<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    #[\Override]
    protected $model = Subscription::class;

    public function definition(): array
    {
        $cycles = ['weekly', 'monthly', 'yearly'];

        return [
            'team_id' => Team::factory(),
            'name' => fake()->company(),
            'price' => fake()->randomFloat(2, 0, 100),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'billing_cycle' => fake()->randomElement($cycles),
            'next_billing_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'url' => fake()->optional(0.7)->url(),
            'description' => fake()->optional(0.5)->sentence(),
            'notes' => fake()->optional(0.3)->paragraph(),
            'is_active' => fake()->boolean(85),
            'category' => fake()->optional(0.7)->randomElement(['Entertainment', 'Productivity', 'Music', 'Cloud Storage', 'Development', 'News', 'Health', 'Education', 'Other']),
        ];
    }
}
