<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Enums\TeamFeature;
use App\Models\User;
use App\Services\McpTokenPermissionService;
use Laravel\Mcp\Request;

/**
 * Registers a read-only vault tool only for E2EE teams whose token can read at
 * least one record type. Mirrors readable-ability gating for ciphertext tools.
 */
trait RegistersForReadableVault
{
    public function shouldRegister(Request $request): bool
    {
        if (app(McpTokenPermissionService::class)->readableTypes() === []) {
            return false;
        }

        $user = $request->user();

        return $user instanceof User && $user->currentTeam?->hasFeature(TeamFeature::E2ee) === true;
    }
}
