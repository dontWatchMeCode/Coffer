<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\DeleteSubscriptionRequest;
use App\Http\Requests\Subscriptions\SaveSubscriptionRequest;
use App\Models\Subscription;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    public function store(SaveSubscriptionRequest $request, Team $currentTeam): RedirectResponse
    {
        $subscription = Subscription::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

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

        $subscription->update($request->validated());

        return to_route('team.subscriptions.show', [
            'current_team' => $currentTeam,
            'subscription' => $subscription->id,
        ]);
    }

    public function destroy(DeleteSubscriptionRequest $request, Team $currentTeam, int $subscription): RedirectResponse
    {
        $subscription = Subscription::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($subscription);

        $subscription->delete();

        return to_route('team.subscriptions.index', [
            'current_team' => $currentTeam,
        ]);
    }
}
