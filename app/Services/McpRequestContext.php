<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\McpToken;
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

        // Prefer the bearer token's team over session state: the middleware
        // binds the validated token, so tool paths that bypass session auth
        // still operate in the team the token was minted for.
        if (app()->bound(McpToken::class)) {
            $tokenTeam = app(McpToken::class)->team;

            if ($tokenTeam instanceof Team && $user->belongsToTeam($tokenTeam)) {
                return [$user, $tokenTeam];
            }

            return Response::error('Current team required.');
        }

        $user->loadTeamContext();
        $team = $user->currentTeam;

        if (! $team instanceof Team || ! $user->belongsToTeam($team)) {
            return Response::error('Current team required.');
        }

        return [$user, $team];
    }
}
