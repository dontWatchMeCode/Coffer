<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class McpRequestContext
{
    /** @return array{0: User, 1: Team}|Response */
    public function resolve(Request $request): array|Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication required.');
        }

        $user->loadTeamContext();
        $team = $user->currentTeam;

        if (! $team instanceof Team || ! $user->belongsToTeam($team)) {
            return Response::error('Current team required.');
        }

        return [$user, $team];
    }
}
