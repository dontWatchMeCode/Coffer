<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Bookmark;
use App\Models\User;

class BookmarkPolicy
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
    public function view(User $user, Bookmark $bookmark): bool
    {
        return $bookmark->team !== null && $user->belongsToTeam($bookmark->team);
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
    public function update(User $user, Bookmark $bookmark): bool
    {
        return $bookmark->team !== null && $user->belongsToTeam($bookmark->team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bookmark $bookmark): bool
    {
        return $bookmark->team !== null && $user->belongsToTeam($bookmark->team);
    }

    public function restore(User $user, Bookmark $bookmark): bool
    {
        return $this->delete($user, $bookmark);
    }

    public function forceDelete(User $user, Bookmark $bookmark): bool
    {
        return $this->delete($user, $bookmark);
    }
}
