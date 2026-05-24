<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Planned = 'planned';
    case Question = 'question';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Dropped = 'dropped';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => [
            'value' => $status->value,
            'label' => str($status->value)->headline()->toString(),
        ], self::cases());
    }

    /**
     * @param  array<mixed>  $statusOptions
     * @return list<array{value: string, label: string}>
     */
    public static function normalizeOptions(array $statusOptions): array
    {
        $normalized = [];

        foreach ($statusOptions as $option) {
            if (! is_array($option)) {
                continue;
            }

            if (! isset($option['value'], $option['label'])) {
                continue;
            }

            $normalized[] = [
                'value' => (string) $option['value'],
                'label' => (string) $option['label'],
            ];
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::options(), 'value');
    }
}
