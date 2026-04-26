<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Contracts\LinkableRecord;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Project;
use App\Models\RecordLink;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * When adding a new linkable type, update:
 * 1. RecordLink::linkableMap() — alias → class mapping
 * 2. HasRecordLinks::titleForModel() — title attribute per class
 * 3. HasRecordLinks::formattedLinkedRecords() — route name and params per class
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
            'title' => static::titleForModel($this),
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
     * Get the title for a linkable model instance.
     */
    public static function titleForModel(Model $model): string
    {
        return match ($model::class) {
            Task::class => $model->getAttribute('title') ?? (string) $model->getKey(),
            Project::class => $model->getAttribute('name') ?? (string) $model->getKey(),
            CalendarEvent::class => $model->getAttribute('title') ?? (string) $model->getKey(),
            Contact::class => $model->getAttribute('name') ?? (string) $model->getKey(),
            Bookmark::class => $model->getAttribute('title') ?? (string) $model->getKey(),
            default => (string) $model->getKey(),
        };
    }

    /**
     * Format linked records for the frontend.
     *
     * @return array<int, array{id: int, type: string, title: string, url: string}>
     */
    public function formattedLinkedRecords(Team $currentTeam): array
    {
        return $this->linkedRecords()->map(function (Model $model) use ($currentTeam): array {
            $title = static::titleForModel($model);

            $routeName = match ($model::class) {
                Task::class => $model->getAttribute('project_id') !== null ? 'team.tasks.edit' : null,
                Project::class => 'team.tasks.show',
                CalendarEvent::class => 'team.calendar.events.edit',
                Contact::class => 'team.contacts.show',
                Bookmark::class => 'team.bookmarks.show',
                default => null,
            };

            $routeParams = match ($model::class) {
                Task::class => [
                    'current_team' => $currentTeam,
                    'project' => $model->getAttribute('project_id'),
                    'task' => $model->getKey(),
                ],
                Project::class => [
                    'current_team' => $currentTeam,
                    'project' => $model->getKey(),
                ],
                default => [
                    'current_team' => $currentTeam,
                    match ($model::class) {
                        CalendarEvent::class => 'event',
                        Contact::class => 'contact',
                        Bookmark::class => 'bookmark',
                        default => $model->getTable(),
                    } => $model->getKey(),
                ],
            };

            return [
                'id' => (int) $model->getKey(),
                'type' => static::typeAliasFor($model::class),
                'title' => $title,
                'url' => $routeName !== null ? route($routeName, $routeParams) : '',
            ];
        })->values()->all();
    }
}
