<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\E2eeKeyEpoch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<E2eeKeyEpoch>
 */
class E2eeKeyEpochFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'epoch' => 1,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
