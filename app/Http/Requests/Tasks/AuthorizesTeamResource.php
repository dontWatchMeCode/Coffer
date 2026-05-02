<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Models\TaskComment;
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

    protected function isCommentOwner(int $commentId, int $taskId): bool
    {
        $team = $this->currentTeam();
        $user = $this->user();

        return $team instanceof Team && $user !== null && TaskComment::query()
            ->whereBelongsTo($team)
            ->where('task_id', $taskId)
            ->whereKey($commentId)
            ->whereBelongsTo($user)
            ->exists();
    }
}
