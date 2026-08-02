<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Actions\Records\SaveSubscription;
use App\Concerns\HandlesTrashedRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\SaveSubscriptionRequest;
use App\Models\Subscription;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    use HandlesTrashedRecords;

    public function __construct(private readonly SaveSubscription $saveSubscription) {}

    public function store(SaveSubscriptionRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', Subscription::class);

        $validated = $request->validated();
        $validated['category'] ??= null;
        $subscription = $this->saveSubscription->execute(new Subscription, $currentTeam, $validated);

        return to_route('team.subscriptions.show', [
            'current_team' => $currentTeam,
            'subscription' => $subscription->id,
        ]);
    }

    public function update(SaveSubscriptionRequest $request, Team $currentTeam, int $subscription): RedirectResponse
    {
        $subscription = Subscription::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($subscription);

        $this->authorize('update', $subscription);

        $validated = $request->validated();
        $validated['category'] ??= null;
        $this->saveSubscription->execute($subscription, $currentTeam, $validated);

        return to_route('team.subscriptions.show', [
            'current_team' => $currentTeam,
            'subscription' => $subscription->id,
        ]);
    }

    public function destroy(Team $currentTeam, int $subscription): RedirectResponse
    {
        $subscription = Subscription::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($subscription);

        $this->authorize('delete', $subscription);

        $subscription->delete();

        return to_route('team.subscriptions.index', [
            'current_team' => $currentTeam,
        ]);
    }

    public function restore(Team $currentTeam, int $subscription): RedirectResponse
    {
        return $this->restoreTrashedRecord($currentTeam, $subscription, Subscription::class, 'team.subscriptions.trash');
    }

    public function forceDestroy(Team $currentTeam, int $subscription): RedirectResponse
    {
        return $this->forceDeleteTrashedRecord($currentTeam, $subscription, Subscription::class, 'team.subscriptions.trash');
    }
}
