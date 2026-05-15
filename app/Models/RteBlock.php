<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $type
 * @property int $position
 * @property array<string, mixed>|null $payload
 */
#[Fillable(['blockable_type', 'blockable_id', 'type', 'position', 'payload'])]
class RteBlock extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function (Builder $builder): void {
            $builder->orderBy('position')->orderBy('id');
        });
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array{id: int, type: string, position: int, payload: array<string, mixed>|null}
     */
    public function toPayloadArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'position' => $this->position,
            'payload' => $this->payload,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
