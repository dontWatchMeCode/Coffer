<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\E2eeMemberKey;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<E2eeMemberKey>
 */
class E2eeMemberKeyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'device_label' => fake()->word(),
            'public_key' => str_replace('=', '', base64_encode(fake()->sha256())).'A',
            'is_active' => true,
        ];
    }
}
