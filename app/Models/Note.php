<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use App\Services\ActivityLogger;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'title'])]
class Note extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use Filterable;

    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (self $note): void {
            if (! $note->isForceDeleting()) {
                return;
            }

            $note->blocks()->delete();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('notes')
            ->logOnly(['title'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return MorphMany<RteBlock, $this>
     */
    public function blocks(): MorphMany
    {
        return $this->morphMany(RteBlock::class, 'blockable');
    }

    public function textExcerpt(int $limit = 180): ?string
    {
        $firstTextBlock = $this->relationLoaded('blocks')
            ? $this->blocks->first(fn ($b): bool => $b->type === 'text' && ! empty($b->payload['content']))
            : $this->blocks()->where('type', 'text')->whereNotNull('payload')->first();

        if ($firstTextBlock && ! empty($firstTextBlock->payload['content'])) {
            return Str::of((string) ($firstTextBlock->payload['content'] ?? ''))->stripTags()->squish()->limit($limit)->toString() ?: null;
        }

        return null;
    }

    public function hasDrawingBlock(): bool
    {
        if ($this->relationLoaded('blocks')) {
            return $this->blocks->contains(fn ($b): bool => $b->type === 'excalidraw');
        }

        return $this->blocks()->where('type', 'excalidraw')->exists();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function firstDrawingPayload(): ?array
    {
        $block = $this->relationLoaded('blocks')
            ? $this->blocks->first(fn ($b): bool => $b->type === 'excalidraw')
            : $this->blocks()->where('type', 'excalidraw')->first();

        $payload = $block?->payload;

        return is_array($payload) ? ($payload['scene'] ?? null) : null;
    }

    /**
     * @param  array<int, array{id?: int, type: string, position: int, payload?: array<string, mixed>|null}>  $blocks
     */
    public function syncBlocks(array $blocks): void
    {
        $this->getConnection()->transaction(function () use ($blocks): void {
            $existingIds = $this->blocks()->pluck('id')->all();

            $incomingIds = collect($blocks)
                ->filter(fn (array $block): bool => isset($block['id']))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => in_array($id, $existingIds, true))
                ->all();

            $removedBlocks = $this->blocks()
                ->whereNotIn('id', $incomingIds)
                ->get(['id', 'type', 'position', 'payload']);

            $removed = $removedBlocks
                ->map(fn (RteBlock $b): array => [
                    'type' => $b->type,
                    'position' => $b->position,
                    'payload' => $b->payload,
                ])
                ->values()
                ->all();

            foreach ($removedBlocks as $removedBlock) {
                $removedBlock->delete();
            }

            $existingBlocks = $this->blocks()
                ->whereIn('id', $incomingIds)
                ->get()
                ->keyBy('id');

            $added = [];
            $updated = [];

            foreach ($blocks as $block) {
                $id = $block['id'] ?? null;
                $data = [
                    'type' => $block['type'],
                    'position' => $block['position'],
                    'payload' => $block['payload'] ?? null,
                ];

                if ($id !== null && in_array((int) $id, $existingIds, true)) {
                    $oldBlock = $existingBlocks->get((int) $id);
                    $this->blocks()->where('id', $id)->update($data);

                    if (! $this->blockPayloadEquals($oldBlock?->payload, $data['payload'], $block['type'])) {
                        $updated[] = [
                            'type' => $block['type'],
                            'position' => $block['position'],
                            'old_payload' => $oldBlock?->payload,
                            'payload' => $block['payload'] ?? null,
                        ];
                    }
                } else {
                    $this->blocks()->create($data);
                    $added[] = [
                        'type' => $block['type'],
                        'position' => $block['position'],
                        'payload' => $block['payload'] ?? null,
                    ];
                }
            }

            $this->touch();

            ActivityLogger::logBlockChanges($this, [
                'added' => $added,
                'updated' => $updated,
                'removed' => $removed,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function blockPayloadEquals(?array $old, ?array $new, string $type): bool
    {
        if ($old === $new) {
            return true;
        }

        if ($old === null || $new === null) {
            return false;
        }

        if ($type === 'excalidraw') {
            return $this->excalidrawPayloadEquals($old, $new);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function excalidrawPayloadEquals(array $old, array $new): bool
    {
        $stripViewport = function (array $payload): array {
            $scene = $payload['scene'] ?? [];
            $appState = $scene['appState'] ?? [];
            unset($appState['scrollX'], $appState['scrollY'], $appState['zoom']);
            $scene['appState'] = $appState;
            $payload['scene'] = $scene;

            return $payload;
        };

        return $stripViewport($old) === $stripViewport($new);
    }
}
