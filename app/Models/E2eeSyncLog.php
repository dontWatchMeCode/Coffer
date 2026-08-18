<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\E2eeSyncLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $change_type
 * @property string|null $record_type
 * @property int|null $record_id
 * @property int|null $epoch_id
 * @property int|null $version
 * @property array<string, mixed>|null $envelope
 * @property bool $is_tombstone
 * @property int|null $file_id
 * @property Carbon|null $created_at
 */
#[Fillable(['team_id', 'change_type', 'record_type', 'record_id', 'epoch_id', 'version', 'envelope', 'is_tombstone', 'file_id', 'created_at'])]
#[Table(name: 'e2ee_sync_log')]
class E2eeSyncLog extends Model
{
    /** @use HasFactory<E2eeSyncLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'envelope' => 'array',
            'is_tombstone' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
