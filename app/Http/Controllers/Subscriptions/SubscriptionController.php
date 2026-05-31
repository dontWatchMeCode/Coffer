<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\DeleteSubscriptionRequest;
use App\Http\Requests\Subscriptions\SaveSubscriptionRequest;
use App\Models\Subscription;
use App\Models\SubscriptionCategory;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    public function store(SaveSubscriptionRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', Subscription::class);

        $validated = $request->validated();
        $categoryName = $validated['category'] ?? null;
        $categoryId = SubscriptionCategory::resolveIdForTeam($categoryName, $currentTeam);

        unset($validated['category']);

        $subscription = Subscription::create([
            ...$validated,
            'team_id' => $currentTeam->id,
        ]);
        $subscription->subscription_category_id = $categoryId;
        $subscription->save();

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
        $oldCategoryId = $subscription->subscription_category_id;
        $categoryName = $validated['category'] ?? null;
        $categoryId = SubscriptionCategory::resolveIdForTeam($categoryName, $currentTeam);

        unset($validated['category']);

        $subscription->update($validated);
        $subscription->subscription_category_id = $categoryId;
        $subscription->save();

        if ($oldCategoryId !== $categoryId && $oldCategoryId) {
            SubscriptionCategory::deleteUnused($currentTeam->id);
        }

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

        $this->authorize('delete', $subscription);

        $subscription->delete();

        return to_route('team.subscriptions.index', [
            'current_team' => $currentTeam,
        ]);
    }

    public function restore(Team $currentTeam, int $subscription): RedirectResponse
    {
        $subscription = Subscription::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($subscription);

        $this->authorize('restore', $subscription);

        $subscription->restore();

        return to_route('team.subscriptions.trash', [
            'current_team' => $currentTeam,
        ]);
    }

    public function forceDestroy(Team $currentTeam, int $subscription): RedirectResponse
    {
        $subscription = Subscription::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($subscription);

        $this->authorize('forceDelete', $subscription);

        $subscription->forceDelete();

        return to_route('team.subscriptions.trash', [
            'current_team' => $currentTeam,
        ]);
    }
}
