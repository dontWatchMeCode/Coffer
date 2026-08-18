<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\E2eeKeyEnvelopeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $epoch_id
 * @property int $member_key_id
 * @property array<string, mixed> $envelope
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['epoch_id', 'member_key_id', 'envelope'])]
class E2eeKeyEnvelope extends Model
{
    /** @use HasFactory<E2eeKeyEnvelopeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'envelope' => 'array',
        ];
    }

    /**
     * @return BelongsTo<E2eeKeyEpoch, $this>
     */
    public function epoch(): BelongsTo
    {
        return $this->belongsTo(E2eeKeyEpoch::class, 'epoch_id');
    }

    /**
     * @return BelongsTo<E2eeMemberKey, $this>
     */
    public function memberKey(): BelongsTo
    {
        return $this->belongsTo(E2eeMemberKey::class, 'member_key_id');
    }
}
