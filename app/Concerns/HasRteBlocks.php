<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\RteBlock;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasRteBlocks
{
    /**
     * @return MorphMany<RteBlock, $this>
     */
    public function blocks(): MorphMany
    {
        return $this->morphMany(RteBlock::class, 'blockable');
    }

    /**
     * @param  array<int, array{id?: int|string, type: string, position: int, payload?: array<string, mixed>|null}>  $blocks
     */
    public function syncBlocks(array $blocks): void
    {
        $this->getConnection()->transaction(function () use ($blocks): void {
            $existingIds = $this->blocks()->pluck('id')->map(fn ($id): int => (int) $id)->all();

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
                ->map(fn (RteBlock $block): array => [
                    'type' => $block->type,
                    'position' => $block->position,
                    'payload' => $block->payload,
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
                $id = isset($block['id']) ? (int) $block['id'] : null;
                $data = [
                    'type' => $block['type'],
                    'position' => $block['position'],
                    'payload' => $block['payload'] ?? null,
                ];

                if ($id !== null && in_array($id, $existingIds, true)) {
                    $oldBlock = $existingBlocks->get($id);
                    $oldType = $oldBlock?->type;
                    $oldPayload = $oldBlock?->payload;

                    $oldBlock?->fill($data)->save();

                    if ($oldType !== $data['type'] || ! $this->blockPayloadEquals($oldPayload, $data['payload'], $data['type'])) {
                        $updated[] = [
                            'type' => $data['type'],
                            'position' => $data['position'],
                            'old_payload' => $oldPayload,
                            'payload' => $data['payload'],
                        ];
                    }
                } else {
                    $this->blocks()->create($data);
                    $added[] = $data;
                }
            }

            $this->afterBlocksSynced();

            ActivityLogger::logBlockChanges($this, [
                'added' => $added,
                'updated' => $updated,
                'removed' => $removed,
            ]);
        });
    }

    protected function afterBlocksSynced(): void
    {
        $this->touch();
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

        if ($type === 'text' || $type === 'mermaid') {
            return ($old['content'] ?? null) === ($new['content'] ?? null);
        }

        return $old == $new;
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
