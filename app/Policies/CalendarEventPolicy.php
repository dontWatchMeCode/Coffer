<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
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
    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->team !== null && $user->belongsToTeam($calendarEvent->team);
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
    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->team !== null && $user->belongsToTeam($calendarEvent->team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->team !== null && $user->belongsToTeam($calendarEvent->team);
    }

    public function restore(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->delete($user, $calendarEvent);
    }

    public function forceDelete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->delete($user, $calendarEvent);
    }
}
