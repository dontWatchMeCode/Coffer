<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Project;
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
            default => (string) $model->getKey(),
        };
    }
}
