<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SubscriptionCategory;
use App\Models\User;

class SubscriptionCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SubscriptionCategory $subscriptionCategory): bool
    {
        return $subscriptionCategory->team !== null && $user->belongsToTeam($subscriptionCategory->team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SubscriptionCategory $subscriptionCategory): bool
    {
        return $subscriptionCategory->team !== null && $user->belongsToTeam($subscriptionCategory->team);
    }

    public function delete(User $user, SubscriptionCategory $subscriptionCategory): bool
    {
        return $subscriptionCategory->team !== null && $user->belongsToTeam($subscriptionCategory->team);
    }
}
