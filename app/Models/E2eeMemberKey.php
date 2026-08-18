<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\E2eeMemberKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'user_id', 'device_label', 'public_key', 'is_active'])]
class E2eeMemberKey extends Model
{
    /** @use HasFactory<E2eeMemberKeyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<E2eeKeyEnvelope, $this>
     */
    public function keyEnvelopes(): HasMany
    {
        return $this->hasMany(E2eeKeyEnvelope::class, 'member_key_id');
    }
}
