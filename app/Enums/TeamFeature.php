<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamFeature: string
{
    case Tasks = 'tasks';
    case Calendar = 'calendar';
    case Contacts = 'contacts';
    case Bookmarks = 'bookmarks';
    case Subscriptions = 'subscriptions';
    case Notes = 'notes';
    case Files = 'files';
    case Log = 'log';
    case Collections = 'collections';

    public function label(): string
    {
        return match ($this) {
            self::Tasks => 'Tasks',
            self::Calendar => 'Calendar',
            self::Contacts => 'Contacts',
            self::Bookmarks => 'Bookmarks',
            self::Subscriptions => 'Subscriptions',
            self::Notes => 'Notes',
            self::Files => 'Files',
            self::Log => 'Log',
            self::Collections => 'Collections',
        };
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $feature): array => [$feature->value => true])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $feature): array => ['value' => $feature->value, 'label' => $feature->label()],
            self::cases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $feature): string => $feature->value, self::cases());
    }
}
