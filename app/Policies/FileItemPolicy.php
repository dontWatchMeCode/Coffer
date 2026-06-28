<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FileItem;
use App\Models\User;

class FileItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FileItem $fileItem): bool
    {
        return $fileItem->team !== null && $user->belongsToTeam($fileItem->team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FileItem $fileItem): bool
    {
        return $fileItem->team !== null && $user->belongsToTeam($fileItem->team);
    }

    public function delete(User $user, FileItem $fileItem): bool
    {
        return $fileItem->team !== null && $user->belongsToTeam($fileItem->team);
    }

    public function restore(User $user, FileItem $fileItem): bool
    {
        return $this->delete($user, $fileItem);
    }

    public function forceDelete(User $user, FileItem $fileItem): bool
    {
        return $this->delete($user, $fileItem);
    }
}
