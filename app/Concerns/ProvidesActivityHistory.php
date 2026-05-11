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
            ->filter(fn (array $item): bool => $this->isSignificantActivity($item))
            ->values()
            ->all();
    }

    /**
     * Batch-build activity history payloads for multiple models.
     *
     * @param  iterable<int, Model>  $models
     * @return array<int, array<int, array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null}>>
     */
    protected function activityHistoryPayloadForModels(iterable $models): array
    {
        $models = collect($models);

        if ($models->isEmpty()) {
            return [];
        }

        $morphClass = $models->first()->getMorphClass();

        throw_if(
            $models->contains(fn (Model $model): bool => $model->getMorphClass() !== $morphClass),
            new \InvalidArgumentException('All models passed to activityHistoryPayloadForModels must share the same morph class.')
        );

        $ids = $models->map->getKey()->all();

        return Activity::where('subject_type', $morphClass)
            ->whereIn('subject_id', $ids)
            ->with('causer')
            ->orderByDesc('id')
            ->get()
            ->mapToGroups(fn (Activity $activity): array => [
                (int) $activity->subject_id => $this->buildActivityItem($activity),
            ])
            ->map(fn ($items) => $items
                ->filter(fn (array $item): bool => $this->isSignificantActivity($item))
                ->values()
                ->all()
            )
            ->all();
    }

    /**
     * @return array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null, relationChanges: array<string, mixed>|null}
     */
    protected function buildActivityItem(Activity $activity): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $changes = $this->filterDrawingViewportChanges($changes);
        $changes = $this->filterEmptyFieldChanges($changes);

        $attributes = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $changedFields = array_values(array_filter(array_keys($attributes), is_string(...)));
        $causerName = $activity->causer?->getAttribute('name');
        $properties = $activity->properties?->toArray() ?? [];
        $relationChanges = $properties['relation_changes'] ?? null;
        $mcpTokenName = $properties['mcp_token_name'] ?? null;

        if (is_string($mcpTokenName) && $mcpTokenName !== '') {
            $causerName = is_string($causerName)
                ? 'MCP '.$mcpTokenName
                : $mcpTokenName;
        }

        return [
            'id' => $activity->id,
            'event' => $activity->event,
            'description' => $activity->description,
            'changedFields' => $changedFields,
            'causerName' => is_string($causerName) ? $causerName : null,
            'createdAt' => $activity->created_at?->format(\DateTimeInterface::ATOM) ?? '',
            'old' => $changes['old'] ?? null,
            'attributes' => $changes['attributes'] ?? null,
            'relationChanges' => is_array($relationChanges) ? $relationChanges : null,
        ];
    }

    /**
     * @param  array{changedFields: array<int, string>, relationChanges: array<string, mixed>|null}  $item
     */
    private function isSignificantActivity(array $item): bool
    {
        return count($item['changedFields']) > 0 || $item['relationChanges'] !== null;
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
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function filterEmptyFieldChanges(array $changes): array
    {
        if (! is_array($changes['attributes'] ?? null)) {
            return $changes;
        }

        foreach (array_keys($changes['attributes']) as $field) {
            if (! is_string($field)) {
                continue;
            }

            $old = $changes['old'][$field] ?? null;
            $new = $changes['attributes'][$field] ?? null;
            if (! $this->isEmptyActivityFieldValue($field, $old)) {
                continue;
            }

            if (! $this->isEmptyActivityFieldValue($field, $new)) {
                continue;
            }

            unset($changes['attributes'][$field]);

            if (isset($changes['old'][$field])) {
                unset($changes['old'][$field]);
            }
        }

        return $changes;
    }

    protected function isEmptyActivityFieldValue(string $field, mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            $value = in_array($field, ['body', 'description'], true)
                ? strip_tags($value)
                : $value;

            return str($value)->squish()->isEmpty();
        }

        if (! is_array($value)) {
            return false;
        }

        if ($field !== 'drawing_data') {
            return $value === [];
        }

        $elements = $value['elements'] ?? [];
        $files = $value['files'] ?? [];

        return is_array($elements) && $elements === []
            && is_array($files) && $files === [];
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
