<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\RecordLink;
use App\Services\ActivitySignificance;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

trait ProvidesActivityHistory
{
    /**
     * Build a paginated activity history payload for JSON responses.
     *
     * @return array{activities: array<int, array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null, relationChanges: array<string, mixed>|null, blockChanges: array<string, mixed>|null}>, total: int, has_more: bool}
     */
    protected function paginatedActivityHistoryPayload(Model $model, int $page = 1, int $perPage = 15): array
    {
        $paginator = Activity::where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->with('causer')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'activities' => $paginator->getCollection()
                ->map(fn (Activity $activity): array => $this->buildActivityItem($activity))
                ->all(),
            'total' => $paginator->total(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }

    /**
     * Batch-build activity history payloads for multiple models.
     *
     * @param  iterable<int, Model>  $models
     * @return array<int, array<int, array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null, relationChanges: array<string, mixed>|null, blockChanges: array<string, mixed>|null}>>
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
            ->map(fn ($items) => $items->values()->all())
            ->all();
    }

    /**
     * @return array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null, relationChanges: array<string, mixed>|null, blockChanges: array<string, mixed>|null}
     */
    protected function buildActivityItem(Activity $activity): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $changes = ActivitySignificance::filterAttributeChanges($changes);

        $attributes = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $changedFields = array_values(array_filter(array_keys($attributes), is_string(...)));
        $causerName = $activity->causer?->getAttribute('name');
        $properties = $activity->properties?->toArray() ?? [];
        $relationChanges = $properties['relation_changes'] ?? null;
        $blockChanges = $properties['block_changes'] ?? null;
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
            'blockChanges' => is_array($blockChanges) ? $blockChanges : null,
        ];
    }

    /**
     * @return array{subject_type: string|null, subject_id: int, total: int}
     */
    protected function activityHistoryConfig(Model $model): array
    {
        $map = array_flip(RecordLink::linkableMap());
        $subjectType = $map[$model::class] ?? null;

        if ($subjectType === null) {
            return [
                'subject_type' => null,
                'subject_id' => (int) $model->getKey(),
                'total' => 0,
            ];
        }

        $total = Activity::where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->count();

        return [
            'subject_type' => $subjectType,
            'subject_id' => (int) $model->getKey(),
            'total' => $total,
        ];
    }
}
