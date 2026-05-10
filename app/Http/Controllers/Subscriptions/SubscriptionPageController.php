<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPageController extends Controller
{
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $subscriptions = Subscription::query()
            ->whereBelongsTo($currentTeam)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return Inertia::render('subscriptions/Index', [
            'subscriptions' => $subscriptions
                ->map(fn (Subscription $subscription): array => $this->formatSubscription($subscription))
                ->values()
                ->all(),
        ]);
    }

    public function show(Request $request, Team $currentTeam, int $subscription): Response
    {
        $subscription = Subscription::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($subscription);

        return Inertia::render('subscriptions/Show', [
            'subscription' => $this->formatSubscription($subscription),
            'recordLinks' => $this->recordLinksPayload($subscription, $currentTeam),
            'recordTags' => $this->recordTagsPayload($subscription, $currentTeam),
            'activityHistory' => $this->activityHistoryPayload($subscription),
        ]);
    }

    /**
     * @return array{id: int, name: string, price?: float|null, currency?: string|null, billingCycle?: string|null, nextBillingDate?: string|null, url?: string|null, description?: string|null, notes?: string|null, isActive: bool, category?: string|null, createdAt?: string|null, updatedAt?: string|null}
     */
    private function formatSubscription(Subscription $subscription): array
    {
        $createdAt = $subscription->getAttribute('created_at');
        $updatedAt = $subscription->getAttribute('updated_at');
        $nextBillingDate = $subscription->getAttribute('next_billing_date');

        return [
            'id' => $subscription->id,
            'name' => $subscription->name,
            'price' => $subscription->price,
            'currency' => $subscription->currency,
            'billingCycle' => $subscription->billing_cycle,
            'nextBillingDate' => $nextBillingDate instanceof \DateTimeInterface
                ? $nextBillingDate->format(\DateTimeInterface::ATOM)
                : null,
            'url' => $subscription->url,
            'description' => $subscription->description,
            'notes' => $subscription->notes,
            'isActive' => $subscription->is_active,
            'category' => $subscription->category,
            'createdAt' => $createdAt instanceof \DateTimeInterface
                ? $createdAt->format(\DateTimeInterface::ATOM)
                : null,
            'updatedAt' => $updatedAt instanceof \DateTimeInterface
                ? $updatedAt->format(\DateTimeInterface::ATOM)
                : null,
        ];
    }
}
