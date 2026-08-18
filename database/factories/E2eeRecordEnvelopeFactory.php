<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\E2eeKeyEpoch;
use App\Models\E2eeRecordEnvelope;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<E2eeRecordEnvelope>
 */
class E2eeRecordEnvelopeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'record_type' => 'note',
            'record_id' => fake()->unique()->numberBetween(1, 100000),
            'epoch_id' => E2eeKeyEpoch::factory(),
            'version' => 1,
            'envelope' => [
                'v' => 1,
                'alg' => 'xchacha20poly1305',
                'kid' => 1,
                'rid' => 'note:1',
                'ver' => 1,
                'n' => 'frozen-nonce',
                'ct' => 'frozen-ciphertext',
            ],
            'is_tombstone' => false,
        ];
    }
}
