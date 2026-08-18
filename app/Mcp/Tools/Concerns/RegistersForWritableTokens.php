<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Enums\TeamFeature;
use App\Models\User;
use App\Services\McpTokenPermissionService;
use Laravel\Mcp\Request;

/**
 * Registers a mutating tool only for writable tokens AND plaintext (non-E2EE)
 * teams. For E2EE teams, mutating record operations are gated by the team's
 * feature flag and the token's ability without exposing plaintext services.
 */
trait RegistersForWritableTokens
{
    public function shouldRegister(Request $request): bool
    {
        if (! app(McpTokenPermissionService::class)->canWriteAnyType()) {
            return false;
        }

        $user = $request->user();

        return ! ($user instanceof User && $user->currentTeam?->hasFeature(TeamFeature::E2ee) === true);
    }
}
