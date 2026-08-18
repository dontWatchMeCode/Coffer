<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\E2eeRecordEnvelopeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $record_type
 * @property int $record_id
 * @property int $epoch_id
 * @property int $version
 * @property array<string, mixed> $envelope
 * @property bool $is_tombstone
 * @property int|null $task_project_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'record_type', 'record_id', 'epoch_id', 'version', 'envelope', 'is_tombstone', 'task_project_id'])]
class E2eeRecordEnvelope extends Model
{
    /** @use HasFactory<E2eeRecordEnvelopeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'envelope' => 'array',
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
