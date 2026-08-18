<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\E2eeFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'file_id', 'epoch_id', 'version', 'disk', 'path', 'size', 'chunk_count', 'is_tombstone'])]
class E2eeFile extends Model
{
    /** @use HasFactory<E2eeFileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_tombstone' => 'boolean',
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
     * @return BelongsTo<E2eeKeyEpoch, $this>
     */
    public function epoch(): BelongsTo
    {
        return $this->belongsTo(E2eeKeyEpoch::class, 'epoch_id');
    }
}
