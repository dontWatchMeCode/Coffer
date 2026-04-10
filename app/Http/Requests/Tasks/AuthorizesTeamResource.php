<?php

namespace App\Http\Requests\Tasks;

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

    protected function currentTeam(): ?Team
    {
        $team = $this->route('current_team');

        return $team instanceof Team ? $team : null;
    }
}
