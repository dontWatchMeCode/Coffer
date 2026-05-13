<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\Team;

trait AuthorizesTeamResource
{
    protected function isTeamMember(): bool
    {
        $team = $this->route('current_team');
        $user = $this->user();

        return $team instanceof Team
            && $user !== null
            && $user->belongsToTeam($team);
    }
}
