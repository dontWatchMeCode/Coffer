<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Exceptions\VaultAuthorizationException;
use App\Mcp\Tools\Concerns\RegistersForReadableVault;
use App\Mcp\Tools\Concerns\ResolvesVaultTeam;
use App\Services\VaultKeyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('vault.keys.get')]
#[Description('Get the current team key epoch, your registered member keys, wrapped team-key envelopes, and active member public keys.')]
#[IsReadOnly]
class VaultKeysGetTool extends Tool
{
    use RegistersForReadableVault;
    use ResolvesVaultTeam;

    public function handle(Request $request, VaultKeyService $keys): Response|ResponseFactory
    {
        $ctx = $this->resolveVaultTeam($request);

        if ($ctx instanceof Response) {
            return $ctx;
        }

        [$user, $team] = $ctx;

        try {
            return Response::structured([
                'vault' => $keys->snapshot($team, $user),
            ]);
        } catch (VaultAuthorizationException $vaultAuthorizationException) {
            return Response::error($vaultAuthorizationException->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
