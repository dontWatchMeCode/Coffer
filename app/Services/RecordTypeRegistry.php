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
    /**
     * Capability flags for each record type.
     *
     * - linkable: the record may participate in record links (implements LinkableRecord / uses HasRecordLinks).
     * - taggable: the record may carry record tags (uses HasRecordTags).
     * - mcp_resource: the record is exposed through MCP tools when non-null.
     *
     * @var array<string, array{prefix: string, global: string, feature: TeamFeature, class: class-string<Model>, columns: list<string>, order: string, linkable: bool, taggable: bool, mcp_resource: string|null, mcp_order: int|null}>|null
     */
    private static ?array $definitions = null;

    /**
     * @return array<string, array{prefix: string, global: string, feature: TeamFeature, class: class-string<Model>, columns: list<string>, order: string, linkable: bool, taggable: bool, mcp_resource: string|null, mcp_order: int|null}>
     */
    public static function definitions(): array
    {
        return self::$definitions ??= [
            'task' => ['prefix' => 't', 'global' => 'tasks', 'feature' => TeamFeature::Tasks, 'class' => Task::class, 'columns' => ['title', 'description'], 'order' => 'title', 'linkable' => true, 'taggable' => true, 'mcp_resource' => 'tasks', 'mcp_order' => 0],
            'contact' => ['prefix' => 'c', 'global' => 'contacts', 'feature' => TeamFeature::Contacts, 'class' => Contact::class, 'columns' => ['name', 'address', 'additional_info'], 'order' => 'name', 'linkable' => true, 'taggable' => true, 'mcp_resource' => 'contacts', 'mcp_order' => 2],
            'calendar_event' => ['prefix' => 'e', 'global' => 'events', 'feature' => TeamFeature::Calendar, 'class' => CalendarEvent::class, 'columns' => ['title', 'description'], 'order' => 'date', 'linkable' => true, 'taggable' => true, 'mcp_resource' => 'calendar', 'mcp_order' => 1],
            'project' => ['prefix' => 'p', 'global' => 'projects', 'feature' => TeamFeature::Tasks, 'class' => Project::class, 'columns' => ['name', 'description'], 'order' => 'name', 'linkable' => true, 'taggable' => true, 'mcp_resource' => null, 'mcp_order' => null],
            'bookmark' => ['prefix' => 'b', 'global' => 'bookmarks', 'feature' => TeamFeature::Bookmarks, 'class' => Bookmark::class, 'columns' => ['title', 'description', 'url'], 'order' => 'title', 'linkable' => true, 'taggable' => true, 'mcp_resource' => 'bookmarks', 'mcp_order' => 3],
            'subscription' => ['prefix' => 's', 'global' => 'subscriptions', 'feature' => TeamFeature::Subscriptions, 'class' => Subscription::class, 'columns' => ['name', 'category', 'description'], 'order' => 'name', 'linkable' => true, 'taggable' => true, 'mcp_resource' => 'subscriptions', 'mcp_order' => 4],
            'note' => ['prefix' => 'n', 'global' => 'notes', 'feature' => TeamFeature::Notes, 'class' => Note::class, 'columns' => ['title'], 'order' => 'title', 'linkable' => true, 'taggable' => true, 'mcp_resource' => 'notes', 'mcp_order' => 5],
            'file' => ['prefix' => 'f', 'global' => 'files', 'feature' => TeamFeature::Files, 'class' => FileItem::class, 'columns' => ['title', 'description', 'original_name'], 'order' => 'title', 'linkable' => true, 'taggable' => true, 'mcp_resource' => 'files', 'mcp_order' => 8],
            'collection' => ['prefix' => 'l', 'global' => 'collections', 'feature' => TeamFeature::Collections, 'class' => RecordCollection::class, 'columns' => ['title', 'description'], 'order' => 'title', 'linkable' => true, 'taggable' => true, 'mcp_resource' => 'collections', 'mcp_order' => 6],
            'log_entry' => ['prefix' => 'g', 'global' => 'log_entries', 'feature' => TeamFeature::Log, 'class' => LogEntry::class, 'columns' => ['body'], 'order' => 'created_at', 'linkable' => false, 'taggable' => false, 'mcp_resource' => 'log_entries', 'mcp_order' => 7],
            'spreadsheet' => ['prefix' => 'x', 'global' => 'spreadsheets', 'feature' => TeamFeature::Spreadsheets, 'class' => SpreadsheetWorkbook::class, 'columns' => ['title'], 'order' => 'title', 'linkable' => true, 'taggable' => true, 'mcp_resource' => null, 'mcp_order' => null],
        ];
    }

    /**
     * @return array{prefix: string, global: string, feature: TeamFeature, class: class-string<Model>, columns: list<string>, order: string, linkable: bool, taggable: bool, mcp_resource: string|null, mcp_order: int|null}|null
     */
    public static function definition(string $type): ?array
    {
        return self::definitions()[$type] ?? null;
    }

    /**
     * @return array<string, array{prefix: string, global: string, feature: TeamFeature, class: class-string<Model>, columns: list<string>, order: string, linkable: bool, taggable: bool, mcp_resource: string|null, mcp_order: int|null}>
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

    public static function isLinkable(string $type): bool
    {
        return self::definition($type)['linkable'] ?? false;
    }

    public static function isTaggable(string $type): bool
    {
        return self::definition($type)['taggable'] ?? false;
    }

    public static function isMcpExposed(string $type): bool
    {
        return (self::definition($type)['mcp_resource'] ?? null) !== null;
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

    /**
     * Records that may participate in record links, keyed by type alias.
     *
     * @return array<string, class-string<Model>>
     */
    public static function linkableMap(): array
    {
        return collect(self::definitions())
            ->filter(fn (array $definition): bool => $definition['linkable'])
            ->mapWithKeys(fn (array $definition, string $type): array => [$type => $definition['class']])
            ->all();
    }

    /**
     * Records that may carry record tags, keyed by type alias.
     *
     * @return array<string, class-string<Model>>
     */
    public static function taggableMap(): array
    {
        return collect(self::definitions())
            ->filter(fn (array $definition): bool => $definition['taggable'])
            ->mapWithKeys(fn (array $definition, string $type): array => [$type => $definition['class']])
            ->all();
    }

    /** @return list<string> */
    public static function mcpTypes(): array
    {
        return array_keys(self::mcpResourceMap());
    }

    /**
     * MCP-exposed record types that may participate in record links, ordered by MCP resource order.
     *
     * @return list<string>
     */
    public static function mcpLinkableTypes(): array
    {
        return array_values(collect(self::mcpResourceMap())
            ->keys()
            ->filter(fn (string $type): bool => self::isLinkable($type))
            ->all());
    }

    /**
     * MCP-exposed record types that may carry record tags, ordered by MCP resource order.
     *
     * @return list<string>
     */
    public static function mcpTaggableTypes(): array
    {
        return array_values(collect(self::mcpResourceMap())
            ->keys()
            ->filter(fn (string $type): bool => self::isTaggable($type))
            ->all());
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

    /**
     * Prefix-to-type mapping for records that may participate in record links.
     *
     * @return array<string, string>
     */
    public static function linkablePrefixMap(): array
    {
        return collect(self::definitions())
            ->filter(fn (array $definition): bool => $definition['linkable'])
            ->mapWithKeys(fn (array $definition, string $type): array => [$definition['prefix'] => $type])
            ->all();
    }
}
