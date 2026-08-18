<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Models\Team;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

/**
 * Resolves the authenticated user and the current team within an MCP vault
 * tool invocation, mirroring McpRequestContext.
 */
trait ResolvesVaultTeam
{
    /**
     * @return array{0: User, 1: Team}|Response
     */
    protected function resolveVaultTeam(Request $request): array|Response
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
