<?php

declare(strict_types=1);

namespace App\Actions\Records;

use App\Models\Subscription;
use App\Models\SubscriptionCategory;
use App\Models\Team;

class SaveSubscription
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Subscription $subscription, Team $team, array $attributes): Subscription
    {
        $hasCategory = array_key_exists('category', $attributes);
        $categoryId = $hasCategory
            ? SubscriptionCategory::resolveIdForTeam($attributes['category'], $team)
            : $subscription->subscription_category_id;
        $oldCategoryId = $subscription->subscription_category_id;

        unset($attributes['category']);

        if (! $subscription->exists) {
            $attributes['team_id'] = $team->id;
        }

        $subscription->fill($attributes);
        $subscription->subscription_category_id = $categoryId;
        $subscription->save();

        if ($oldCategoryId && $oldCategoryId !== $categoryId) {
            SubscriptionCategory::deleteUnused($team->id);
        }

        return $subscription;
    }
}
