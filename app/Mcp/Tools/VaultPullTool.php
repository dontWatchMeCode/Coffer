<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\RegistersForReadableVault;
use App\Mcp\Tools\Concerns\ResolvesVaultTeam;
use App\Services\VaultRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('vault.pull')]
#[Description('Pull encrypted changes after a sync cursor, optionally filtered by record type. Returns opaque ciphertext envelopes.')]
#[IsReadOnly]
class VaultPullTool extends Tool
{
    use RegistersForReadableVault;
    use ResolvesVaultTeam;

    public function handle(Request $request, VaultRecordService $records): Response|ResponseFactory
    {
        $ctx = $this->resolveVaultTeam($request);

        if ($ctx instanceof Response) {
            return $ctx;
        }

        [, $team] = $ctx;

        $data = $request->validate([
            'cursor' => ['required', 'integer', 'min:0'],
            'limit' => ['integer', 'min:1', 'max:1000'],
            'types' => ['nullable', 'array'],
        ]);

        $pull = $records->pull(
            $team,
            (int) $data['cursor'],
            (int) ($data['limit'] ?? 200),
            $data['types'] ?? null,
        );

        return Response::structured($pull);
    }

    public function schema(JsonSchema $schema): array
    {
        return VaultToolSchemas::cursor($schema);
    }
}
