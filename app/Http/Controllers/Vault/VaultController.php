<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vault;

use App\Exceptions\VaultAuthorizationException;
use App\Exceptions\VaultStaleWriteException;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\VaultFileService;
use App\Services\VaultKeyService;
use App\Services\VaultRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Web transport for the browser E2EE client. Mirrors the MCP vault.* tools but
 * is authenticated with the user's session and team membership in place of an
 * MCP token. Only ciphertext moves over the wire.
 */
class VaultController extends Controller
{
    public function __construct(
        private readonly VaultKeyService $keys,
        private readonly VaultRecordService $records,
        private readonly VaultFileService $files,
    ) {}

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_if(! $user instanceof User, 401);

        return $user;
    }

    public function keys(Request $request, Team $currentTeam): JsonResponse
    {
        return response()->json(['vault' => $this->keys->snapshot($currentTeam, $this->user($request))]);
    }

    public function registerKey(Request $request, Team $currentTeam): JsonResponse
    {
        $data = $request->validate([
            'device_label' => ['required', 'string', 'max:120'],
            'public_key' => ['required', 'string', 'max:64'],
        ]);

        try {
            return response()->json(['member_key' => $this->keys->register($currentTeam, $this->user($request), $data)]);
        } catch (VaultAuthorizationException $vaultAuthorizationException) {
            return response()->json(['error' => $vaultAuthorizationException->getMessage()], 403);
        }
    }

    public function putEnvelopes(Request $request, Team $currentTeam): JsonResponse
    {
        $data = $request->validate([
            'epoch_id' => ['required', 'integer'],
            'envelopes' => ['required', 'array', 'min:1'],
        ]);

        try {
            return response()->json($this->keys->storeWrappedEnvelopes(
                $currentTeam,
                $this->user($request),
                (int) $data['epoch_id'],
                $data['envelopes'],
            ));
        } catch (VaultAuthorizationException $vaultAuthorizationException) {
            return response()->json(['error' => $vaultAuthorizationException->getMessage()], 403);
        }
    }

    public function rotate(Request $request, Team $currentTeam): JsonResponse
    {
        return response()->json(['epoch' => $this->keys->openNextEpoch($currentTeam, $this->user($request))]);
    }

    public function pull(Request $request, Team $currentTeam): JsonResponse
    {
        $data = $request->validate([
            'cursor' => ['required', 'integer', 'min:0'],
            'limit' => ['integer', 'min:1', 'max:1000'],
            'types' => ['nullable', 'array'],
        ]);

        return response()->json($this->records->pull(
            $currentTeam,
            (int) $data['cursor'],
            (int) ($data['limit'] ?? 200),
            $data['types'] ?? null,
        ));
    }

    public function push(Request $request, Team $currentTeam): JsonResponse
    {
        $data = $request->validate([
            'records' => ['required', 'array', 'min:1'],
        ]);

        try {
            return response()->json($this->records->push($currentTeam, $data['records']));
        } catch (VaultStaleWriteException $vaultStaleWriteException) {
            return response()->json(['error' => $vaultStaleWriteException->getMessage()], 409);
        }
    }

    public function filePut(Request $request, Team $currentTeam): JsonResponse
    {
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
            return response()->json(['error' => 'Invalid ciphertext payload.'], 422);
        }

        try {
            $result = $this->files->put(
                $currentTeam,
                (int) $data['epoch_id'],
                (int) $data['file_id'],
                (int) $data['version'],
                $data['metadata'],
                $content,
                $data['chunk_count'] ?? '1',
            );

            return response()->json($result);
        } catch (VaultStaleWriteException $vaultStaleWriteException) {
            return response()->json(['error' => $vaultStaleWriteException->getMessage()], 409);
        }
    }

    public function fileGet(Request $request, Team $currentTeam, int $file): JsonResponse
    {
        $fileData = $this->files->get($currentTeam, $file);

        if ($fileData === null) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        return response()->json(['file' => $fileData]);
    }
}
