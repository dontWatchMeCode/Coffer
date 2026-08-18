<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Exceptions\VaultStaleWriteException;
use App\Mcp\Tools\Concerns\RegistersForWritableVault;
use App\Mcp\Tools\Concerns\ResolvesVaultTeam;
use App\Services\VaultRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('vault.push')]
#[Description('Push ciphertext record envelopes with optimistic versions. Rejects stale writes; identical retries are idempotent.')]
class VaultPushTool extends Tool
{
    use RegistersForWritableVault;
    use ResolvesVaultTeam;

    public function handle(Request $request, VaultRecordService $records): Response|ResponseFactory
    {
        $ctx = $this->resolveVaultTeam($request);

        if ($ctx instanceof Response) {
            return $ctx;
        }

        [, $team] = $ctx;

        $data = $request->validate([
            'records' => ['required', 'array', 'min:1'],
        ]);

        try {
            return Response::structured($records->push($team, $data['records']));
        } catch (VaultStaleWriteException $vaultStaleWriteException) {
            return Response::error($vaultStaleWriteException->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return VaultToolSchemas::push($schema);
    }
}
