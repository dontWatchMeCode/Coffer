<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\E2eeChangeType;
use App\Exceptions\VaultStaleWriteException;
use App\Models\E2eeFile;
use App\Models\E2eeKeyEpoch;
use App\Models\E2eeSyncLog;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VaultFileService
{
    public const DISK = 'local';

    public function __construct(
        private readonly VaultKeyService $keyService,
    ) {}

    /**
     * Store an encrypted file blob plus its opaque metadata envelope. The
     * server stores ciphertext only; filename/MIME/plaintext bytes never reach
     * Laravel.
     *
     * @param  array<string, mixed>  $metadataEnvelope
     * @return array<string, mixed>
     */
    public function put(
        Team $team,
        int $epochId,
        int $fileId,
        int $version,
        array $metadataEnvelope,
        string $ciphertext,
        string $chunkCount,
    ): array {
        $active = $this->keyService->activeEpoch($team);

        if (! $active instanceof E2eeKeyEpoch || (int) $active->id !== $epochId) {
            throw new VaultStaleWriteException('File was encrypted under a retired key epoch.', 409);
        }

        $path = 'vault/'.$team->id.'/'.($fileId).'-'.Str::random(24);

        DB::transaction(function () use ($team, $epochId, $fileId, $version, $metadataEnvelope, $path, $ciphertext, $chunkCount): void {
            $existing = E2eeFile::query()
                ->where('team_id', $team->id)
                ->where('file_id', $fileId)
                ->first();

            // Preserve the previous blob path so we can clean it up after the
            // new blob is written and the row repointed.
            $previousPath = $existing?->path;

            Storage::disk(self::DISK)->put($path, $ciphertext);

            $file = E2eeFile::query()->updateOrCreate(
                ['team_id' => $team->id, 'file_id' => $fileId],
                [
                    'epoch_id' => $epochId,
                    'version' => $version,
                    'disk' => self::DISK,
                    'path' => $path,
                    'size' => strlen($ciphertext),
                    'chunk_count' => $chunkCount,
                    'is_tombstone' => false,
                ],
            );

            // Remove the orphaned previous blob after the row is repointed.
            if ($previousPath !== null && $previousPath !== $path) {
                Storage::disk(self::DISK)->delete($previousPath);
            }

            E2eeSyncLog::query()->create([
                'team_id' => $team->id,
                'change_type' => E2eeChangeType::File->value,
                'record_type' => 'file',
                'record_id' => $fileId,
                'epoch_id' => $epochId,
                'version' => $version,
                'envelope' => $metadataEnvelope,
                'file_id' => $file->id,
                'created_at' => now(),
            ]);
        });

        return ['file_id' => $fileId, 'version' => $version];
    }

    /**
     * Fetch an encrypted file blob and its metadata envelope. Returns the
     * ciphertext bytes base64-encoded plus envelope metadata. No decryption.
     *
     * @return array{metadata: array<string, mixed>, content: string, chunk_count: string, version: int}|null
     */
    public function get(Team $team, int $fileId): ?array
    {
        $file = E2eeFile::query()
            ->where('team_id', $team->id)
            ->where('file_id', $fileId)
            ->where('is_tombstone', false)
            ->first();

        if ($file === null) {
            return null;
        }

        $sync = E2eeSyncLog::query()
            ->where('team_id', $team->id)
            ->where('change_type', E2eeChangeType::File->value)
            ->where('file_id', $file->id)
            ->latest('id')
            ->first();

        $content = Storage::disk($file->disk)->get($file->path);

        $metadata = $sync === null ? [] : ($sync->envelope ?? []);

        return [
            'metadata' => $metadata,
            'content' => base64_encode($content ?? ''),
            'chunk_count' => $file->chunk_count ?? '1',
            'version' => $file->version,
        ];
    }

    /**
     * Remove encrypted file blobs and their sync entries for a tombstoned file.
     */
    public function deleteBlob(Team $team, int $fileId): void
    {
        $file = E2eeFile::query()->where('team_id', $team->id)->where('file_id', $fileId)->first();

        if ($file === null) {
            return;
        }

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }

    /**
     * Tombstone a file's encrypted blob: mark the row deleted and purge its
     * ciphertext from storage so the file is no longer downloadable or left on
     * disk. Called when a 'file' record is tombstoned through vault.push.
     */
    public function tombstone(Team $team, int $fileId): void
    {
        DB::transaction(function () use ($team, $fileId): void {
            $file = E2eeFile::query()
                ->where('team_id', $team->id)
                ->where('file_id', $fileId)
                ->first();

            if ($file === null) {
                return;
            }

            Storage::disk($file->disk)->delete($file->path);
            $file->update(['is_tombstone' => true]);

            E2eeSyncLog::query()->create([
                'team_id' => $team->id,
                'change_type' => E2eeChangeType::File->value,
                'record_type' => 'file',
                'record_id' => $fileId,
                'epoch_id' => $file->epoch_id,
                'version' => $file->version,
                'file_id' => $file->id,
                'is_tombstone' => true,
                'created_at' => now(),
            ]);
        });
    }
}
