<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\RegistersForReadableVault;
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

#[Name('vault.files.get')]
#[Description('Fetch an encrypted file blob as ciphertext plus its opaque metadata envelope. No plaintext is served.')]
class VaultFilesGetTool extends Tool
{
    use RegistersForReadableVault;
    use ResolvesVaultTeam;

    public function handle(Request $request, VaultFileService $files, McpTokenPermissionService $permissions): Response|ResponseFactory
    {
        $ctx = $this->resolveVaultTeam($request);

        if ($ctx instanceof Response) {
            return $ctx;
        }

        [, $team] = $ctx;

        if (! $permissions->can('file', 'read')) {
            return Response::error('Permission denied.');
        }

        $data = $request->validate([
            'file_id' => ['required', 'integer'],
        ]);

        $file = $files->get($team, (int) $data['file_id']);

        if ($file === null) {
            return Response::error('File not found.');
        }

        return Response::structured(['file' => $file]);
    }

    public function schema(JsonSchema $schema): array
    {
        return VaultToolSchemas::fileGet($schema);
    }
}
