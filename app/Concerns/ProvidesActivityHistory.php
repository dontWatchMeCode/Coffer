<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

trait ProvidesActivityHistory
{
    /**
     * Build a reusable activity history payload for Inertia.
     *
     * @return array<int, array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null}>
     */
    protected function activityHistoryPayload(Model $model): array
    {
        return Activity::where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->with('causer')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Activity $activity): array => $this->buildActivityItem($activity))
            ->filter(fn (array $item): bool => count($item['changedFields']) > 0)
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null}
     */
    protected function buildActivityItem(Activity $activity): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $changes = $this->filterDrawingViewportChanges($changes);

        $attributes = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $changedFields = array_values(array_filter(array_keys($attributes), is_string(...)));
        $causerName = $activity->causer?->getAttribute('name');

        return [
            'id' => $activity->id,
            'event' => $activity->event,
            'description' => $activity->description,
            'changedFields' => $changedFields,
            'causerName' => is_string($causerName) ? $causerName : null,
            'createdAt' => $activity->created_at?->format(\DateTimeInterface::ATOM) ?? '',
            'old' => $changes['old'] ?? null,
            'attributes' => $changes['attributes'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function filterDrawingViewportChanges(array $changes): array
    {
        if (! isset($changes['attributes']['drawing_data'])) {
            return $changes;
        }

        $old = $changes['old']['drawing_data'] ?? null;
        $new = $changes['attributes']['drawing_data'] ?? null;

        if (! is_array($old) || ! is_array($new)) {
            return $changes;
        }

        if ($this->drawingDataEqualsIgnoringViewport($old, $new)) {
            unset($changes['attributes']['drawing_data']);
            unset($changes['old']['drawing_data']);
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    protected function drawingDataEqualsIgnoringViewport(array $old, array $new): bool
    {
        $withoutViewport = function (array $data): array {
            $data['appState'] ??= [];

            unset(
                $data['appState']['scrollX'],
                $data['appState']['scrollY'],
                $data['appState']['zoom'],
            );

            return $data;
        };

        return $withoutViewport($old) === $withoutViewport($new);
    }
}
