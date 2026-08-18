<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\E2eeEpochStatus;
use Database\Factories\E2eeKeyEpochFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $epoch
 * @property E2eeEpochStatus $status
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'epoch', 'status', 'created_by'])]
class E2eeKeyEpoch extends Model
{
    /** @use HasFactory<E2eeKeyEpochFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => E2eeEpochStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<E2eeKeyEnvelope, $this>
     */
    public function keyEnvelopes(): HasMany
    {
        return $this->hasMany(E2eeKeyEnvelope::class, 'epoch_id');
    }

    /**
     * @return HasMany<E2eeRecordEnvelope, $this>
     */
    public function recordEnvelopes(): HasMany
    {
        return $this->hasMany(E2eeRecordEnvelope::class, 'epoch_id');
    }
}
