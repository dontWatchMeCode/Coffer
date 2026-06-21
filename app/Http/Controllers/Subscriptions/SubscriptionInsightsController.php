<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Enums\InsightsTimeRange;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionInsightsController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $range = InsightsTimeRange::fromRequest($request);
        $today = CarbonImmutable::today();
        $window = $range->window($today);

        /** @var EloquentCollection<int, Subscription> $subscriptions */
        $subscriptions = Subscription::query()
            ->whereBelongsTo($currentTeam)
            ->with('subscriptionCategory:id,name')
            ->get();

        /** @var EloquentCollection<int, Subscription> $activeSubscriptions */
        $activeSubscriptions = $subscriptions
            ->filter(fn (Subscription $subscription): bool => $subscription->is_active)
            ->values();

        return Inertia::render('subscriptions/Insights', [
            'range' => $range->value,
            'rangeOptions' => InsightsTimeRange::options(),
            'insights' => [
                'kpis' => $this->kpis($activeSubscriptions, $today),
                'spendTrend' => $this->spendTrend($subscriptions, $window),
                'categoryBreakdown' => $this->categoryBreakdown($activeSubscriptions),
            ],
        ]);
    }

    /**
     * @param  EloquentCollection<int, Subscription>  $subscriptions
     * @return array{monthlySpend: string, activeCount: int, upcomingRenewals: int}
     */
    private function kpis(EloquentCollection $subscriptions, CarbonImmutable $today): array
    {
        $monthlySpend = $subscriptions->sum(fn (Subscription $subscription): float => $this->monthlyPrice($subscription));

        $upcomingRenewals = $subscriptions
            ->filter(function (Subscription $subscription) use ($today): bool {
                $date = $subscription->getAttribute('next_billing_date');

                return $date instanceof CarbonInterface
                    && $date->greaterThanOrEqualTo($today)
                    && $date->lessThanOrEqualTo($today->copy()->addDays(30));
            })
            ->count();

        return [
            'monthlySpend' => number_format((float) $monthlySpend, 2, '.', ''),
            'activeCount' => $subscriptions->count(),
            'upcomingRenewals' => $upcomingRenewals,
        ];
    }

    /**
     * @param  EloquentCollection<int, Subscription>  $subscriptions
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $window
     * @return list<array{month: string, spend: float}>
     */
    private function spendTrend(EloquentCollection $subscriptions, array $window): array
    {
        $buckets = InsightsTimeRange::monthBuckets($window);

        foreach ($buckets as $monthKey => &$bucket) {
            $monthEnd = CarbonImmutable::parse($monthKey.'-01')->endOfMonth();

            $monthStart = $monthEnd->startOfMonth();

            $spend = $subscriptions
                ->filter(function (Subscription $subscription) use ($monthEnd, $monthStart): bool {
                    $firstBillingDate = $subscription->getAttribute('first_billing_date');
                    $nextBillingDate = $subscription->getAttribute('next_billing_date');
                    $createdAt = $subscription->getAttribute('created_at');
                    $startDate = $firstBillingDate instanceof CarbonInterface
                        ? $firstBillingDate
                        : ($subscription->is_active ? $createdAt : $nextBillingDate);

                    $hasStarted = $startDate instanceof CarbonInterface
                        && $startDate->lessThanOrEqualTo($monthEnd);

                    if (! $hasStarted) {
                        return false;
                    }

                    return $subscription->is_active
                        || ($nextBillingDate instanceof CarbonInterface
                            && $nextBillingDate->greaterThanOrEqualTo($monthStart));
                })
                ->sum(fn (Subscription $subscription): float => $this->monthlyPrice($subscription));

            $bucket['spend'] = round($spend, 2);
        }

        return array_values($buckets);
    }

    /**
     * @param  EloquentCollection<int, Subscription>  $subscriptions
     * @return list<array{category: string, spend: float}>
     */
    private function categoryBreakdown(EloquentCollection $subscriptions): array
    {
        /** @var Collection<string, float> $grouped */
        $grouped = $subscriptions
            ->groupBy(fn (Subscription $subscription): string => $subscription->subscriptionCategory->name ?? 'Uncategorized')
            ->map(fn (Collection $items): float => round($items->sum(fn (Subscription $subscription): float => $this->monthlyPrice($subscription)), 2));

        return array_values($grouped
            ->sortByDesc(fn (float $spend): float => $spend)
            ->map(fn (float $spend, string $category): array => [
                'category' => $category,
                'spend' => $spend,
            ])
            ->all());
    }

    private function monthlyPrice(Subscription $subscription): float
    {
        return match ($subscription->billing_cycle) {
            'weekly' => (float) $subscription->price * 52 / 12,
            'yearly' => (float) $subscription->price / 12,
            default => (float) $subscription->price,
        };
    }
}
