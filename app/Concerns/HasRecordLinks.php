<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Contracts\LinkableRecord;
use App\Models\Note;
use App\Models\RecordLink;
use App\Models\Team;
use App\Services\ActivityLogger;
use App\Services\RecordLinkHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * When adding a new linkable type, update:
 * 1. RecordLink::linkableMap() — alias → class mapping
 * 2. RecordLinkHelper — title, URL generation, and routability rules
 * 3. RecordSearchRegistry — prefixes, searchable columns, and sort order
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
                $links = RecordLink::queryForModel($model->linkableType(), $model->getKey(), (int) $teamId)->get();

                foreach ($links as $link) {
                    ActivityLogger::logLinkCleanup($model, $link);
                    $link->delete();
                }
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

            foreach ($models as $model) {
                $results->push($model);
            }
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
     * @return array<int, array{id: int, type: string, title: string, url: string, preview: string|null, format?: string|null, drawingData?: array<string, mixed>|null}>
     */
    public function formattedLinkedRecords(Team $currentTeam, bool $includeDrawingData = false): array
    {
        return $this->linkedRecords()->map(function (Model $model) use ($currentTeam, $includeDrawingData): array {
            $record = [
                'id' => (int) $model->getKey(),
                'type' => static::typeAliasFor($model::class),
                'title' => RecordLinkHelper::titleForModel($model),
                'url' => RecordLinkHelper::urlForModel($model, $currentTeam),
                'preview' => RecordLinkHelper::previewForModel($model),
            ];

            if ($model instanceof Note) {
                $record['format'] = $model->format;
                $record['drawingData'] = $includeDrawingData ? $model->drawing_data : null;
            }

            return $record;
        })->values()->all();
    }
}
