<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Exceptions\VaultStaleWriteException;
use App\Mcp\Tools\Concerns\RegistersForWritableVault;
use App\Mcp\Tools\Concerns\ResolvesVaultTeam;
use App\Services\McpTokenPermissionService;
use App\Services\VaultFileService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('vault.files.put')]
#[Description('Store an encrypted file blob (base64 ciphertext) plus its opaque metadata envelope.')]
class VaultFilesPutTool extends Tool
{
    use RegistersForWritableVault;
    use ResolvesVaultTeam;

    public function handle(Request $request, VaultFileService $files, McpTokenPermissionService $permissions): Response|ResponseFactory
    {
        $ctx = $this->resolveVaultTeam($request);

        if ($ctx instanceof Response) {
            return $ctx;
        }

        [, $team] = $ctx;

        if (! $permissions->can('file', 'write')) {
            return Response::error('Permission denied.');
        }

        $data = $request->validate([
            'file_id' => ['required', 'integer'],
            'epoch_id' => ['required', 'integer'],
            'version' => ['required', 'integer'],
            'metadata' => ['required', 'array'],
            'content' => ['required', 'string'],
            'chunk_count' => ['nullable', 'string'],
        ]);

        $content = base64_decode((string) $data['content'], true);

        if ($content === false) {
            return Response::error('Invalid ciphertext payload.');
        }

        try {
            $result = $files->put(
                $team,
                (int) $data['epoch_id'],
                (int) $data['file_id'],
                (int) $data['version'],
                $data['metadata'],
                $content,
                $data['chunk_count'] ?? '1',
            );

            return Response::structured($result);
        } catch (VaultStaleWriteException $vaultStaleWriteException) {
            return Response::error($vaultStaleWriteException->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return VaultToolSchemas::filePut($schema);
    }
}
