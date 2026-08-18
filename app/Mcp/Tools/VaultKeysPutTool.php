<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Exceptions\VaultAuthorizationException;
use App\Mcp\Tools\Concerns\RegistersForWritableVault;
use App\Mcp\Tools\Concerns\ResolvesVaultTeam;
use App\Services\VaultKeyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('vault.keys.put')]
#[Description('Register a member device public key, or store wrapped team-key envelopes for an epoch, or open the next key epoch.')]
class VaultKeysPutTool extends Tool
{
    use RegistersForWritableVault;
    use ResolvesVaultTeam;

    public function handle(Request $request, VaultKeyService $keys): Response|ResponseFactory
    {
        $ctx = $this->resolveVaultTeam($request);

        if ($ctx instanceof Response) {
            return $ctx;
        }

        [$user, $team] = $ctx;

        try {
            if ($request->get('public_key') !== null) {
                $data = $request->validate([
                    'device_label' => ['required', 'string', 'max:120'],
                    'public_key' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
                ]);

                return Response::structured([
                    'member_key' => $keys->register($team, $user, $data),
                ]);
            }

            if ($request->get('envelopes') !== null && $request->get('epoch_id') !== null) {
                $data = $request->validate([
                    'epoch_id' => ['required', 'integer'],
                    'envelopes' => ['required', 'array'],
                ]);

                return Response::structured([
                    'stored' => $keys->storeWrappedEnvelopes($team, $user, (int) $data['epoch_id'], $data['envelopes']),
                ]);
            }

            if ($request->get('rotate') === true) {
                return Response::structured([
                    'epoch' => $keys->openNextEpoch($team, $user),
                ]);
            }

            return Response::error('vault.keys.put requires public_key, envelopes, or rotate.');
        } catch (VaultAuthorizationException $vaultAuthorizationException) {
            return Response::error($vaultAuthorizationException->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'device_label' => $schema->string()->description('Register a member key: human-readable device label.'),
            'public_key' => $schema->string()->description('Register a member key: base64url X25519 public key.'),
            'epoch_id' => $schema->integer()->description('Store wrapped envelopes for this epoch.'),
            'envelopes' => $schema->array()->items(
                $schema->object([
                    'public_key' => $schema->string()->required(),
                    'envelope' => $schema->object()->required(),
                ]),
            )->description('Wrapped team-key envelopes (requires epoch_id).'),
            'rotate' => $schema->boolean()->description('Open the next key epoch (retires the current one).'),
        ];
    }
}
