<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\E2eeKeyEnvelope;
use App\Models\E2eeKeyEpoch;
use App\Models\E2eeMemberKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<E2eeKeyEnvelope>
 */
class E2eeKeyEnvelopeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'epoch_id' => E2eeKeyEpoch::factory(),
            'member_key_id' => E2eeMemberKey::factory(),
            'envelope' => [
                'v' => 1,
                'alg' => 'x25519+hkdf+xchacha20poly1305',
                'epk' => 'frozen-ephemeral-public',
                'n' => 'frozen-nonce',
                'ct' => 'frozen-ciphertext',
            ],
        ];
    }
}
