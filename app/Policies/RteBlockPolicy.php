<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RteBlock;
use App\Models\User;

class RteBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RteBlock $rteBlock): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, RteBlock $rteBlock): bool
    {
        return true;
    }

    public function delete(User $user, RteBlock $rteBlock): bool
    {
        return true;
    }
}
