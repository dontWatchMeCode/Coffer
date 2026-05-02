<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecordCollection;
use App\Models\User;

class RecordCollectionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RecordCollection $collection): bool
    {
        return $collection->team !== null && $user->belongsToTeam($collection->team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RecordCollection $collection): bool
    {
        return $collection->team !== null && $user->belongsToTeam($collection->team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecordCollection $collection): bool
    {
        return $collection->team !== null && $user->belongsToTeam($collection->team);
    }
}
