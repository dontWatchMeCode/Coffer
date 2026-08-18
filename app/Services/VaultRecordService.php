<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\E2eeChangeType;
use App\Exceptions\VaultStaleWriteException;
use App\Models\E2eeKeyEpoch;
use App\Models\E2eeRecordEnvelope;
use App\Models\E2eeSyncLog;
use App\Models\McpToken;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class VaultRecordService
{
    public function __construct(
        private readonly VaultKeyService $keyService,
        private readonly McpTokenPermissionService $permissions,
        private readonly VaultFileService $fileService,
    ) {}

    /**
     * Validate that the active epoch is referenced before accepting a write.
     */
    private function assertActiveEpoch(Team $team, int $epochId): void
    {
        $active = $this->keyService->activeEpoch($team);

        if (! $active instanceof E2eeKeyEpoch || (int) $active->id !== $epochId) {
            throw new VaultStaleWriteException('Record was encrypted under a retired key epoch.', 409);
        }
    }

    /**
     * Push encrypted record envelopes. Returns accepted/conflict result per
     * record. Stale writes (mismatched optimistic version) are rejected with a
     * 409 conflict; identical retries are idempotent.
     *
     * @param  array<int, array{type: string, id: int, epoch_id: int, version: int, envelope: array<string, mixed>, tombstone?: bool, task_project_id?: int|null}>  $records
     * @return array{accepted: array<int, mixed>, conflicts: array<int, mixed>}
     */
    public function push(Team $team, array $records): array
    {
        $accepted = [];
        $conflicts = [];

        foreach ($records as $record) {
            [$type, $id, $epochId, $version] = [
                $record['type'],
                $record['id'],
                $record['epoch_id'],
                $record['version'],
            ];

            $tombstone = (bool) ($record['tombstone'] ?? false);
            $taskProjectId = $record['task_project_id'] ?? null;

            // Enforce token abilities and task-project scope against plaintext
            // operational metadata only.
            $scopeData = $taskProjectId !== null ? ['project_id' => $taskProjectId] : null;
            if (! $this->permissions->can($type, 'write', null, $scopeData)) {
                $conflicts[] = ['type' => $type, 'id' => $id, 'reason' => 'permission_denied'];

                continue;
            }

            // Reject writes encrypted under a retired epoch.
            if (! $tombstone) {
                try {
                    $this->assertActiveEpoch($team, $epochId);
                } catch (VaultStaleWriteException) {
                    $conflicts[] = ['type' => $type, 'id' => $id, 'reason' => 'stale_epoch', 'current_epoch' => $this->keyService->activeEpoch($team)?->id];

                    continue;
                }
            }

            // Serialize the read-decision-apply per record to close the
            // lost-update race between concurrent pushes of the same record.
            $outcome = DB::transaction(
                fn (): array => $this->resolveRecordWrite($team, $record, $type, $id, $epochId, $version, $tombstone, $taskProjectId),
            );

            $status = $outcome['status'];

            if ($status === 'duplicate') {
                $accepted[] = ['type' => $type, 'id' => $id, 'version' => $version, 'status' => 'duplicate'];
            } elseif ($status === 'version_conflict') {
                $conflicts[] = [
                    'type' => $type,
                    'id' => $id,
                    'reason' => 'version_conflict',
                    'expected' => $outcome['expected'] ?? null,
                    'received' => $version,
                ];
            } else {
                $accepted[] = ['type' => $type, 'id' => $id, 'version' => $version, 'status' => 'accepted'];
            }
        }

        return [
            'accepted' => $accepted,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Pull encrypted changes after a cursor, optionally limiting to record
     * types. key_epoch and file changes are always returned so clients never
     * silently miss rotation or file events. The returned cursor accounts for
     * every row scanned in the batch, so typed/ability-filtered pulls advance
     * past skipped rows without losing them.
     *
     * @param  list<string>|null  $types
     * @return array{next_cursor: int, changes: array<int, array<string, mixed>>}
     */
    public function pull(Team $team, int $cursor, int $limit, ?array $types = null): array
    {
        $scanLimit = min($limit * 4, 1000);

        $rows = E2eeSyncLog::query()
            ->where('team_id', $team->id)
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit($scanLimit)
            ->get();

        // Ability allow-list for record changes (operational metadata only).
        // key_epoch and file rows are always returned regardless of abilities.
        $readable = $this->permissions->currentToken() instanceof McpToken
            ? $this->permissions->readableTypes()
            : null;

        $changes = [];
        $scanned = $cursor;

        foreach ($rows as $row) {
            $scanned = $row->id;

            // key_epoch and file changes are never suppressed.
            if ($row->change_type !== E2eeChangeType::Record->value) {
                $changes[] = $this->rowToChange($row);

                continue;
            }

            // Record-type filter.
            if (is_array($types) && $types !== [] && ! in_array($row->record_type, $types, true)) {
                continue;
            }

            // Token ability filter.
            if ($readable !== null && ! in_array($row->record_type, $readable, true)) {
                continue;
            }

            $changes[] = $this->rowToChange($row);
        }

        return ['next_cursor' => $scanned, 'changes' => $changes];
    }

    /**
     * @return array<string, mixed>
     */
    private function rowToChange(E2eeSyncLog $row): array
    {
        $item = [
            'cursor' => $row->id,
            'change_type' => $row->change_type,
            'is_tombstone' => $row->is_tombstone,
        ];

        if ($row->change_type === E2eeChangeType::KeyEpoch->value) {
            $item['epoch_id'] = $row->epoch_id;

            return $item;
        }

        $item['record_type'] = $row->record_type;
        $item['record_id'] = $row->record_id;
        $item['version'] = $row->version;
        $item['epoch_id'] = $row->epoch_id;
        $item['envelope'] = $row->envelope;

        if ($row->file_id !== null) {
            $item['file_id'] = $row->file_id;
        }

        return $item;
    }

    /**
     * Decide and, where accepted, apply a single record write under a
     * serialized transaction. Returns the outcome for the caller to report.
     *
     * @param  array<string, mixed>  $record
     * @return array{status: string, expected?: int}
     */
    private function resolveRecordWrite(Team $team, array $record, string $type, int $id, int $epochId, int $version, bool $tombstone, ?int $taskProjectId): array
    {
        $envelope = is_array($record['envelope']) ? $record['envelope'] : [];

        $existing = E2eeRecordEnvelope::query()
            ->where('team_id', $team->id)
            ->where('record_type', $type)
            ->where('record_id', $id)
            ->lockForUpdate()
            ->first();

        // No prior write: accept.
        if ($existing === null) {
            $this->applyRecord($team, $type, $id, $epochId, $version, $envelope, $tombstone, $taskProjectId);

            return ['status' => 'accepted'];
        }

        $existingVersion = (int) $existing->version;

        // Idempotent retry: identical envelope already stored at this version.
        if ($existingVersion === $version && $existing->envelope === $envelope) {
            return ['status' => 'duplicate'];
        }

        // Equal version with different ciphertext means two writers produced the
        // same optimistic version: ambiguous, conflict.
        if ($existingVersion === $version) {
            return ['status' => 'version_conflict', 'expected' => $existingVersion];
        }

        // Stale write: existing is ahead of what the client prepared.
        if ($existingVersion > $version) {
            return ['status' => 'version_conflict', 'expected' => $existingVersion];
        }

        // Last-writer-wins: the incoming write is newer than what exists.
        $this->applyRecord($team, $type, $id, $epochId, $version, $envelope, $tombstone, $taskProjectId);

        return ['status' => 'accepted'];
    }

    /**
     * @param  array<string, mixed>  $envelope
     */
    private function applyRecord(Team $team, string $type, int $id, int $epochId, int $version, array $envelope, bool $tombstone, ?int $taskProjectId): void
    {
        DB::transaction(function () use ($team, $type, $id, $epochId, $version, $envelope, $tombstone, $taskProjectId): void {
            E2eeRecordEnvelope::query()->updateOrCreate(
                [
                    'team_id' => $team->id,
                    'record_type' => $type,
                    'record_id' => $id,
                ],
                [
                    'epoch_id' => $epochId,
                    'version' => $version,
                    'envelope' => $envelope,
                    'is_tombstone' => $tombstone,
                    'task_project_id' => $tombstone ? null : $taskProjectId,
                ],
            );

            E2eeSyncLog::query()->create([
                'team_id' => $team->id,
                'change_type' => E2eeChangeType::Record->value,
                'record_type' => $type,
                'record_id' => $id,
                'epoch_id' => $epochId,
                'version' => $version,
                'envelope' => $envelope,
                'is_tombstone' => $tombstone,
                'created_at' => now(),
            ]);

            // A Tombstone for a file record must hide and purge its encrypted
            // blob so tombstoned files are not downloadable or left on disk.
            if ($tombstone && $type === 'file') {
                $this->fileService->tombstone($team, $id);
            }
        });
    }
}
