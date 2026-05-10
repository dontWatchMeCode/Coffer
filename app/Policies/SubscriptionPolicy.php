<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $subscription->team !== null && $user->belongsToTeam($subscription->team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $subscription->team !== null && $user->belongsToTeam($subscription->team);
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $subscription->team !== null && $user->belongsToTeam($subscription->team);
    }
}
