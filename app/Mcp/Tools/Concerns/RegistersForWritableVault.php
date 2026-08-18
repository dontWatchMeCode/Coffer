<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Enums\TeamFeature;
use App\Models\User;
use App\Services\McpTokenPermissionService;
use Laravel\Mcp\Request;

/**
 * Registers a mutating vault tool only for E2EE teams whose token can write at
 * least one record type. Mirrors RegistersForWritableTokens for ciphertext
 * transport tools.
 */
trait RegistersForWritableVault
{
    public function shouldRegister(Request $request): bool
    {
        if (! app(McpTokenPermissionService::class)->canWriteAnyType()) {
            return false;
        }

        $user = $request->user();

        return $user instanceof User && $user->currentTeam?->hasFeature(TeamFeature::E2ee) === true;
    }
}
