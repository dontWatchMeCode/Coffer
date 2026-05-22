<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\RecordLink;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivitySignificance;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

trait ProvidesActivityHistory
{
    /** @var list<string> */
    private const ATTRIBUTE_CHANGE_SIDES = ['old', 'attributes'];

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
            'activities' => $this->buildActivityItems($paginator->getCollection()),
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
            ->pipe(fn ($activities): array => $this->groupedActivityItemsBySubject($activities));
    }

    /**
     * @param  iterable<int, Activity>  $activities
     * @return array<int, array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null, relationChanges: array<string, mixed>|null, blockChanges: array<string, mixed>|null}>
     */
    protected function buildActivityItems(iterable $activities): array
    {
        $activities = collect($activities)->values();
        $context = $this->taskAssigneeDisplayContext($activities);

        return $activities
            ->map(fn (Activity $activity): array => $this->buildActivityItem($activity, $context['userNamesById'], $context['taskMorphClass']))
            ->values()
            ->all();
    }

    /**
     * @param  iterable<int, Activity>  $activities
     * @return array{taskMorphClass: string, userNamesById: array<int, string>}
     */
    protected function taskAssigneeDisplayContext(iterable $activities): array
    {
        $taskMorphClass = (new Task)->getMorphClass();

        return [
            'taskMorphClass' => $taskMorphClass,
            'userNamesById' => $this->userNamesForTaskAssigneeChanges($activities, $taskMorphClass),
        ];
    }

    /**
     * @param  iterable<int, Activity>  $activities
     * @return array<int, string>
     */
    protected function userNamesForTaskAssigneeChanges(iterable $activities, string $taskMorphClass): array
    {
        $userIds = collect($activities)
            ->filter(fn (Activity $activity): bool => $activity->subject_type === $taskMorphClass)
            ->flatMap(fn (Activity $activity): array => $this->taskAssigneeChangeUserIds($activity))
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereKey($userIds->all())
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int|string $id): array => [(int) $id => $name])
            ->all();
    }

    /**
     * @return list<int>
     */
    protected function taskAssigneeChangeUserIds(Activity $activity): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $ids = [];

        foreach (self::ATTRIBUTE_CHANGE_SIDES as $side) {
            $value = $changes[$side]['assigned_to'] ?? null;

            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $ids[] = (int) $value;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<int, string>  $userNamesById
     * @return array<string, mixed>
     */
    protected function replaceTaskAssigneeIds(Activity $activity, array $changes, array $userNamesById, string $taskMorphClass): array
    {
        if ($activity->subject_type !== $taskMorphClass) {
            return $changes;
        }

        foreach (self::ATTRIBUTE_CHANGE_SIDES as $side) {
            if (! is_array($changes[$side] ?? null)) {
                continue;
            }

            if (! array_key_exists('assigned_to', $changes[$side])) {
                continue;
            }

            $changes[$side]['assigned_to'] = $this->displayTaskAssignee($changes[$side]['assigned_to'], $userNamesById);
        }

        return $changes;
    }

    /**
     * @param  array<int, string>  $userNamesById
     */
    protected function displayTaskAssignee(mixed $value, array $userNamesById): string
    {
        if (in_array($value, [null, '', 0, '0'], true)) {
            return 'Unassigned';
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $userId = (int) $value;

            return $userNamesById[$userId] ?? 'User #'.$userId;
        }

        return is_string($value) ? $value : (json_encode($value) ?: '');
    }

    /**
     * @param  iterable<int, Activity>  $activities
     * @return array<int, array<int, array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null, relationChanges: array<string, mixed>|null, blockChanges: array<string, mixed>|null}>>
     */
    protected function groupedActivityItemsBySubject(iterable $activities): array
    {
        $activities = collect($activities)->values();
        $context = $this->taskAssigneeDisplayContext($activities);
        $grouped = [];

        foreach ($activities as $activity) {
            $grouped[(int) $activity->subject_id][] = $this->buildActivityItem($activity, $context['userNamesById'], $context['taskMorphClass']);
        }

        return $grouped;
    }

    /**
     * @param  array<int, string>  $userNamesById
     * @return array{id: int, event: string|null, description: string, changedFields: array<int, string>, causerName: string|null, createdAt: string, old: array<string, mixed>|null, attributes: array<string, mixed>|null, relationChanges: array<string, mixed>|null, blockChanges: array<string, mixed>|null}
     */
    protected function buildActivityItem(Activity $activity, array $userNamesById = [], ?string $taskMorphClass = null): array
    {
        if ($taskMorphClass === null) {
            $context = $this->taskAssigneeDisplayContext([$activity]);
            $taskMorphClass = $context['taskMorphClass'];
            $userNamesById = $userNamesById === [] ? $context['userNamesById'] : $userNamesById;
        }

        $changes = $activity->attribute_changes?->toArray() ?? [];
        $changes = ActivitySignificance::filterAttributeChanges($changes);
        $changes = $this->replaceTaskAssigneeIds($activity, $changes, $userNamesById, $taskMorphClass);

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
