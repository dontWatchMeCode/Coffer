<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Enums\TeamFeature;
use App\Models\Team;
use App\Models\User;
use Laravel\Mcp\Request;

/**
 * Registers a tool only when the current team does NOT have E2EE enabled.
 * Used to hide the plaintext records.* tools for E2EE teams.
 */
trait RegistersForPlaintextTeams
{
    public function shouldRegister(Request $request): bool
    {
        return $this->currentPlaintextTeam($request)?->hasFeature(TeamFeature::E2ee) !== true;
    }

    protected function currentPlaintextTeam(Request $request): ?Team
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        return $user->currentTeam;
    }
}
