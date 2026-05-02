<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecordLink;
use App\Models\User;

class RecordLinkPolicy
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
    public function view(User $user, RecordLink $recordLink): bool
    {
        return $recordLink->team !== null && $user->belongsToTeam($recordLink->team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecordLink $recordLink): bool
    {
        return $recordLink->team !== null && $user->belongsToTeam($recordLink->team);
    }
}
