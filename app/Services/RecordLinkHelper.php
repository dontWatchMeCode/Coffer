<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

class RecordLinkHelper
{
    /**
     * Generate a URL for a linkable model instance.
     */
    public static function urlForModel(Model $model, Team $currentTeam): string
    {
        $routeName = match ($model::class) {
            Task::class => $model->getAttribute('project_id') !== null ? 'team.tasks.edit' : null,
            Project::class => 'team.tasks.show',
            CalendarEvent::class => 'team.calendar.events.edit',
            Contact::class => 'team.contacts.show',
            Bookmark::class => 'team.bookmarks.show',
            Subscription::class => 'team.subscriptions.show',
            Note::class => 'team.notes.show',
            RecordCollection::class => 'team.collections.show',
            default => null,
        };

        if ($routeName === null) {
            return '';
        }

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
                    Subscription::class => 'subscription',
                    Note::class => 'note',
                    RecordCollection::class => 'collection',
                    default => $model->getTable(),
                } => $model->getKey(),
            ],
        };

        return route($routeName, $routeParams);
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
            Subscription::class => $model->getAttribute('name') ?? (string) $model->getKey(),
            Note::class => $model->getAttribute('title') ?? (string) $model->getKey(),
            RecordCollection::class => $model->getAttribute('title') ?? (string) $model->getKey(),
            default => (string) $model->getKey(),
        };
    }

    /**
     * Get preview text for a linkable model instance.
     */
    public static function previewForModel(Model $model): ?string
    {
        return match ($model::class) {
            Task::class => $model->getAttribute('description'),
            Project::class => $model->getAttribute('description'),
            CalendarEvent::class => $model->getAttribute('description'),
            Contact::class => $model->getAttribute('additional_info'),
            Bookmark::class => $model->getAttribute('description'),
            Subscription::class => $model->getAttribute('description'),
            Note::class => str($model->getAttribute('body') ?? '')->stripTags()->squish()->limit(180)->toString() ?: null,
            RecordCollection::class => $model->getAttribute('description'),
            default => null,
        };
    }
}
