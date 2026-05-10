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
use Illuminate\Database\Eloquent\Model;

class RecordSearchRegistry
{
    /**
     * @var array<string, array{prefix: string, global: string, class: class-string<Model>, columns: list<string>, order: string}>|null
     */
    private static ?array $definitions = null;

    /**
     * @return array<string, array{prefix: string, global: string, class: class-string<Model>, columns: list<string>, order: string}>
     */
    public static function definitions(): array
    {
        return self::$definitions ??= [
            'task' => ['prefix' => 't', 'global' => 'tasks', 'class' => Task::class, 'columns' => ['title', 'description'], 'order' => 'title'],
            'contact' => ['prefix' => 'c', 'global' => 'contacts', 'class' => Contact::class, 'columns' => ['name', 'address', 'additional_info'], 'order' => 'name'],
            'calendar_event' => ['prefix' => 'e', 'global' => 'events', 'class' => CalendarEvent::class, 'columns' => ['title', 'description'], 'order' => 'date'],
            'project' => ['prefix' => 'p', 'global' => 'projects', 'class' => Project::class, 'columns' => ['name', 'description'], 'order' => 'name'],
            'bookmark' => ['prefix' => 'b', 'global' => 'bookmarks', 'class' => Bookmark::class, 'columns' => ['title', 'description', 'url'], 'order' => 'title'],
            'subscription' => ['prefix' => 's', 'global' => 'subscriptions', 'class' => Subscription::class, 'columns' => ['name', 'category', 'description'], 'order' => 'name'],
            'note' => ['prefix' => 'n', 'global' => 'notes', 'class' => Note::class, 'columns' => ['title', 'body'], 'order' => 'title'],
            'collection' => ['prefix' => 'l', 'global' => 'collections', 'class' => RecordCollection::class, 'columns' => ['title', 'description'], 'order' => 'title'],
        ];
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public static function linkableMap(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition, string $alias): array => [$alias => $definition['class']])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function globalPrefixMap(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition): array => [$definition['prefix'] => $definition['global']])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function linkablePrefixMap(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition, string $alias): array => [$definition['prefix'] => $alias])
            ->all();
    }
}
