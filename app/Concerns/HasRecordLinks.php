<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Contracts\LinkableRecord;
use App\Models\RecordLink;
use App\Models\Team;
use App\Services\RecordLinkHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * When adding a new linkable type, update:
 * 1. RecordLink::linkableMap() — alias → class mapping
 * 2. RecordLinkHelper — title, URL generation, and routability rules
 * 3. SearchPrefixes — prefix mappings for global and record-link search
 * 4. RecordLinkController::candidates() — searchable columns and order column per class
 */
trait HasRecordLinks
{
    /**
     * Clean up record links when a linkable model is deleted.
     */
    public static function bootHasRecordLinks(): void
    {
        static::deleting(function (Model $model): void {
            if (! $model instanceof LinkableRecord) {
                return;
            }

            $teamId = $model->getAttribute('team_id');

            if ($teamId !== null) {
                RecordLink::queryForModel($model->linkableType(), $model->getKey(), (int) $teamId)->delete();
            }
        });
    }

    /**
     * The allowed model classes that can participate in record links.
     *
     * @return array<class-string<Model>>
     */
    public static function linkableTypes(): array
    {
        return array_values(RecordLink::linkableMap());
    }

    /**
     * Get the morph type alias used in the record_links table.
     */
    public function linkableType(): string
    {
        return static::class;
    }

    /**
     * Load all records linked to this model.
     *
     * @return Collection<int, Model>
     */
    public function linkedRecords(): Collection
    {
        $type = $this->linkableType();
        $id = $this->getKey();
        $teamId = $this->getAttribute('team_id');

        if ($teamId === null) {
            return new Collection;
        }

        $grouped = RecordLink::linkedIdsGroupedByClass($type, $id, (int) $teamId);

        if ($grouped === []) {
            return new Collection;
        }

        $results = new Collection;

        foreach ($grouped as $modelClass => $ids) {
            if (! in_array($modelClass, static::linkableTypes(), true)) {
                continue;
            }

            $models = $modelClass::query()
                ->where('team_id', $teamId)
                ->whereIn('id', array_unique($ids))
                ->limit(100)
                ->get();
            $results = $results->merge($models);
        }

        return $results;
    }

    /**
     * Get the context for record link UI.
     *
     * @return array{type: string, id: int, title: string}
     */
    public function recordLinkContext(): array
    {
        return [
            'type' => static::typeAliasFor(static::class),
            'id' => (int) $this->getKey(),
            'title' => RecordLinkHelper::titleForModel($this),
        ];
    }

    /**
     * Get the type alias for a linkable model class.
     */
    public static function typeAliasFor(string $class): string
    {
        return array_flip(RecordLink::linkableMap())[$class] ?? 'unknown';
    }

    /**
     * Format linked records for the frontend.
     *
     * @return array<int, array{id: int, type: string, title: string, url: string, preview: string|null}>
     */
    public function formattedLinkedRecords(Team $currentTeam): array
    {
        return $this->linkedRecords()->map(fn (Model $model): array => [
            'id' => (int) $model->getKey(),
            'type' => static::typeAliasFor($model::class),
            'title' => RecordLinkHelper::titleForModel($model),
            'url' => RecordLinkHelper::urlForModel($model, $currentTeam),
            'preview' => RecordLinkHelper::previewForModel($model),
        ])->values()->all();
    }
}
