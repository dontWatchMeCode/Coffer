<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TeamFeature;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\FileItem;
use App\Models\LogEntry;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\SpreadsheetWorkbook;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

class RecordSearchRegistry
{
    /**
     * @var array<string, array{prefix: string, global: string, feature: string, class: class-string<Model>, columns: list<string>, order: string}>|null
     */
    private static ?array $definitions = null;

    /**
     * @return array<string, array{prefix: string, global: string, feature: string, class: class-string<Model>, columns: list<string>, order: string}>
     */
    public static function definitions(): array
    {
        return self::$definitions ??= [
            'task' => ['prefix' => 't', 'global' => 'tasks', 'feature' => 'tasks', 'class' => Task::class, 'columns' => ['title', 'description'], 'order' => 'title'],
            'contact' => ['prefix' => 'c', 'global' => 'contacts', 'feature' => 'contacts', 'class' => Contact::class, 'columns' => ['name', 'address', 'additional_info'], 'order' => 'name'],
            'calendar_event' => ['prefix' => 'e', 'global' => 'events', 'feature' => 'calendar', 'class' => CalendarEvent::class, 'columns' => ['title', 'description'], 'order' => 'date'],
            'project' => ['prefix' => 'p', 'global' => 'projects', 'feature' => 'tasks', 'class' => Project::class, 'columns' => ['name', 'description'], 'order' => 'name'],
            'bookmark' => ['prefix' => 'b', 'global' => 'bookmarks', 'feature' => 'bookmarks', 'class' => Bookmark::class, 'columns' => ['title', 'description', 'url'], 'order' => 'title'],
            'subscription' => ['prefix' => 's', 'global' => 'subscriptions', 'feature' => 'subscriptions', 'class' => Subscription::class, 'columns' => ['name', 'category', 'description'], 'order' => 'name'],
            'note' => ['prefix' => 'n', 'global' => 'notes', 'feature' => 'notes', 'class' => Note::class, 'columns' => ['title'], 'order' => 'title'],
            'file' => ['prefix' => 'f', 'global' => 'files', 'feature' => 'files', 'class' => FileItem::class, 'columns' => ['title', 'description', 'original_name'], 'order' => 'title'],
            'collection' => ['prefix' => 'l', 'global' => 'collections', 'feature' => 'collections', 'class' => RecordCollection::class, 'columns' => ['title', 'description'], 'order' => 'title'],
            'log_entry' => ['prefix' => 'g', 'global' => 'log_entries', 'feature' => 'log', 'class' => LogEntry::class, 'columns' => ['body'], 'order' => 'created_at'],
            'spreadsheet' => ['prefix' => 'x', 'global' => 'spreadsheets', 'feature' => 'spreadsheets', 'class' => SpreadsheetWorkbook::class, 'columns' => ['title'], 'order' => 'title'],
        ];
    }

    /**
     * @return array<string, array{prefix: string, global: string, feature: string, class: class-string<Model>, columns: list<string>, order: string}>
     */
    public static function enabledDefinitions(Team $team): array
    {
        return collect(self::definitions())
            ->filter(fn (array $definition): bool => $team->hasFeature($definition['feature']))
            ->all();
    }

    public static function featureForType(string $type): ?TeamFeature
    {
        $feature = self::definitions()[$type]['feature'] ?? null;

        return is_string($feature) ? TeamFeature::tryFrom($feature) : null;
    }

    public static function teamAllowsType(Team $team, string $type): bool
    {
        $feature = self::featureForType($type);

        return ! $feature instanceof TeamFeature || $team->hasFeature($feature);
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
