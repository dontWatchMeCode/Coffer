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

class RecordTypeRegistry
{
    /** @var array<string, array{prefix: string, global: string, feature: TeamFeature, class: class-string<Model>, columns: list<string>, order: string, mcp_resource: string|null, mcp_order: int|null}>|null */
    private static ?array $definitions = null;

    /**
     * @return array<string, array{prefix: string, global: string, feature: TeamFeature, class: class-string<Model>, columns: list<string>, order: string, mcp_resource: string|null, mcp_order: int|null}>
     */
    public static function definitions(): array
    {
        return self::$definitions ??= [
            'task' => ['prefix' => 't', 'global' => 'tasks', 'feature' => TeamFeature::Tasks, 'class' => Task::class, 'columns' => ['title', 'description'], 'order' => 'title', 'mcp_resource' => 'tasks', 'mcp_order' => 0],
            'contact' => ['prefix' => 'c', 'global' => 'contacts', 'feature' => TeamFeature::Contacts, 'class' => Contact::class, 'columns' => ['name', 'address', 'additional_info'], 'order' => 'name', 'mcp_resource' => 'contacts', 'mcp_order' => 2],
            'calendar_event' => ['prefix' => 'e', 'global' => 'events', 'feature' => TeamFeature::Calendar, 'class' => CalendarEvent::class, 'columns' => ['title', 'description'], 'order' => 'date', 'mcp_resource' => 'calendar', 'mcp_order' => 1],
            'project' => ['prefix' => 'p', 'global' => 'projects', 'feature' => TeamFeature::Tasks, 'class' => Project::class, 'columns' => ['name', 'description'], 'order' => 'name', 'mcp_resource' => null, 'mcp_order' => null],
            'bookmark' => ['prefix' => 'b', 'global' => 'bookmarks', 'feature' => TeamFeature::Bookmarks, 'class' => Bookmark::class, 'columns' => ['title', 'description', 'url'], 'order' => 'title', 'mcp_resource' => 'bookmarks', 'mcp_order' => 3],
            'subscription' => ['prefix' => 's', 'global' => 'subscriptions', 'feature' => TeamFeature::Subscriptions, 'class' => Subscription::class, 'columns' => ['name', 'category', 'description'], 'order' => 'name', 'mcp_resource' => 'subscriptions', 'mcp_order' => 4],
            'note' => ['prefix' => 'n', 'global' => 'notes', 'feature' => TeamFeature::Notes, 'class' => Note::class, 'columns' => ['title'], 'order' => 'title', 'mcp_resource' => 'notes', 'mcp_order' => 5],
            'file' => ['prefix' => 'f', 'global' => 'files', 'feature' => TeamFeature::Files, 'class' => FileItem::class, 'columns' => ['title', 'description', 'original_name'], 'order' => 'title', 'mcp_resource' => 'files', 'mcp_order' => 8],
            'collection' => ['prefix' => 'l', 'global' => 'collections', 'feature' => TeamFeature::Collections, 'class' => RecordCollection::class, 'columns' => ['title', 'description'], 'order' => 'title', 'mcp_resource' => 'collections', 'mcp_order' => 6],
            'log_entry' => ['prefix' => 'g', 'global' => 'log_entries', 'feature' => TeamFeature::Log, 'class' => LogEntry::class, 'columns' => ['body'], 'order' => 'created_at', 'mcp_resource' => 'log_entries', 'mcp_order' => 7],
            'spreadsheet' => ['prefix' => 'x', 'global' => 'spreadsheets', 'feature' => TeamFeature::Spreadsheets, 'class' => SpreadsheetWorkbook::class, 'columns' => ['title'], 'order' => 'title', 'mcp_resource' => null, 'mcp_order' => null],
        ];
    }

    /**
     * @return array{prefix: string, global: string, feature: TeamFeature, class: class-string<Model>, columns: list<string>, order: string, mcp_resource: string|null, mcp_order: int|null}|null
     */
    public static function definition(string $type): ?array
    {
        return self::definitions()[$type] ?? null;
    }

    /**
     * @return array<string, array{prefix: string, global: string, feature: TeamFeature, class: class-string<Model>, columns: list<string>, order: string, mcp_resource: string|null, mcp_order: int|null}>
     */
    public static function enabledDefinitions(Team $team): array
    {
        return array_filter(
            self::definitions(),
            fn (array $definition): bool => $team->hasFeature($definition['feature']),
        );
    }

    public static function teamAllowsType(Team $team, string $type): bool
    {
        $definition = self::definition($type);

        return $definition === null || $team->hasFeature($definition['feature']);
    }

    /** @return class-string<Model>|null */
    public static function classFor(string $type): ?string
    {
        return self::definition($type)['class'] ?? null;
    }

    public static function typeForClass(string $class): ?string
    {
        foreach (self::definitions() as $type => $definition) {
            if ($definition['class'] === $class) {
                return $type;
            }
        }

        return null;
    }

    /** @return array<string, class-string<Model>> */
    public static function linkableMap(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition, string $type): array => [$type => $definition['class']])
            ->all();
    }

    /** @return list<string> */
    public static function mcpTypes(): array
    {
        return array_keys(self::mcpResourceMap());
    }

    /** @return array<string, string> */
    public static function mcpResourceMap(): array
    {
        return collect(self::definitions())
            ->filter(fn (array $definition): bool => $definition['mcp_resource'] !== null)
            ->sortBy('mcp_order')
            ->mapWithKeys(fn (array $definition, string $type): array => [$type => $definition['mcp_resource']])
            ->all();
    }

    public static function mcpResourceFor(string $type): ?string
    {
        return self::definition($type)['mcp_resource'] ?? null;
    }

    /** @return list<string> */
    public static function searchableColumnsFor(string $type): array
    {
        return self::definition($type)['columns'] ?? [];
    }

    /** @return array<string, string> */
    public static function globalPrefixMap(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition): array => [$definition['prefix'] => $definition['global']])
            ->all();
    }

    /** @return array<string, string> */
    public static function linkablePrefixMap(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition, string $type): array => [$definition['prefix'] => $type])
            ->all();
    }
}
