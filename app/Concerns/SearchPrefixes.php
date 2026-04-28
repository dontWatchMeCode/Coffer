<?php

declare(strict_types=1);

namespace App\Concerns;

class SearchPrefixes
{
    /**
     * Prefix to category key mapping for global search.
     *
     * Keep in sync with SearchPrefixTooltip.vue.
     *
     * @return array<string, string>
     */
    public static function globalMap(): array
    {
        return [
            't' => 'tasks',
            'c' => 'contacts',
            'e' => 'events',
            'p' => 'projects',
            'b' => 'bookmarks',
            'n' => 'notes',
        ];
    }

    /**
     * Prefix to linkable type alias mapping for record-link candidate search.
     *
     * Keep in sync with RecordLink::linkableMap().
     *
     * @return array<string, string>
     */
    public static function linkableMap(): array
    {
        return [
            't' => 'task',
            'c' => 'contact',
            'e' => 'calendar_event',
            'p' => 'project',
            'b' => 'bookmark',
            'n' => 'note',
        ];
    }
}
