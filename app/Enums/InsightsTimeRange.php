<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

enum InsightsTimeRange: string
{
    case ThisMonth = 'this_month';
    case Last3Months = 'last_3_months';
    case ThisYear = 'this_year';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $range): array => [
            'value' => $range->value,
            'label' => match ($range) {
                self::ThisMonth => 'This month',
                self::Last3Months => 'Last 3 months',
                self::ThisYear => 'This year',
            },
        ], self::cases());
    }

    public static function fromRequest(Request $request): self
    {
        return self::tryFrom($request->string('range')->toString()) ?? self::Last3Months;
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public function window(CarbonImmutable $today): array
    {
        return match ($this) {
            self::ThisMonth => [
                'start' => $today->copy()->startOfMonth(),
                'end' => $today->copy()->endOfMonth(),
            ],
            self::Last3Months => [
                'start' => $today->copy()->subMonthsNoOverflow(2)->startOfMonth(),
                'end' => $today->copy()->endOfMonth(),
            ],
            self::ThisYear => [
                'start' => $today->copy()->startOfYear(),
                'end' => $today->copy()->endOfYear(),
            ],
        };
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $window
     * @return array<string, array{month: string}>
     */
    public static function monthBuckets(array $window): array
    {
        $buckets = [];

        foreach ($window['start']->startOfMonth()->monthsUntil($window['end']->endOfMonth()) as $date) {
            $monthKey = $date->format('Y-m');
            $buckets[$monthKey] = ['month' => $monthKey];
        }

        return $buckets;
    }
}
