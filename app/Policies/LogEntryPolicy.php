<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LogEntry;
use App\Models\User;

class LogEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LogEntry $logEntry): bool
    {
        return $logEntry->team !== null && $user->belongsToTeam($logEntry->team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LogEntry $logEntry): bool
    {
        return $logEntry->team !== null && $user->belongsToTeam($logEntry->team);
    }

    public function delete(User $user, LogEntry $logEntry): bool
    {
        return $logEntry->team !== null && $user->belongsToTeam($logEntry->team);
    }

    public function restore(User $user, LogEntry $logEntry): bool
    {
        return $this->delete($user, $logEntry);
    }

    public function forceDelete(User $user, LogEntry $logEntry): bool
    {
        return $this->delete($user, $logEntry);
    }
}
