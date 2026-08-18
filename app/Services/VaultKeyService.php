<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\E2eeChangeType;
use App\Enums\E2eeEpochStatus;
use App\Exceptions\VaultAuthorizationException;
use App\Models\E2eeKeyEnvelope;
use App\Models\E2eeKeyEpoch;
use App\Models\E2eeMemberKey;
use App\Models\E2eeSyncLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VaultKeyService
{
    /**
     * Register a device public key for an active team member.
     *
     * @param  array<string, mixed>  $data  with 'device_label' and 'public_key'
     * @return array<string, mixed>
     */
    public function register(Team $team, User $user, array $data): array
    {
        $publicKey = str_replace('=', '', (string) $data['public_key']);

        $existing = E2eeMemberKey::query()
            ->where('team_id', $team->id)
            ->where('public_key', $publicKey)
            ->first();

        if ($existing !== null) {
            if ($existing->user_id !== $user->id) {
                throw new VaultAuthorizationException('Public key already registered in this team.');
            }

            // Re-registering a previously revoked key reactivates it so a
            // re-added member can re-pair without generating a new keypair.
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return $this->memberKeyPayload($existing, $team);
        }

        $memberKey = E2eeMemberKey::query()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'device_label' => mb_substr((string) $data['device_label'], 0, 120),
            'public_key' => $publicKey,
            'is_active' => true,
        ]);

        return $this->memberKeyPayload($memberKey, $team);
    }

    /**
     * Get the current team key snapshot for a member: active epoch, member key,
     * the member's wrapped envelope for the active epoch, and active member
     * public keys (for authorizing newly accepted members).
     *
     * @return array<string, mixed>
     */
    public function snapshot(Team $team, User $user): array
    {
        $epoch = $this->activeEpoch($team);
        $memberKeys = $this->memberKeys($team, $user);

        $activeKeys = E2eeMemberKey::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->whereHas('teams', fn ($t) => $t->whereKey($team->id)))
            ->get();

        $snapshot = [
            'epoch' => $epoch instanceof E2eeKeyEpoch ? [
                'id' => $epoch->id,
                'epoch' => $epoch->epoch,
                'status' => $epoch->status->value,
            ] : null,
            'member_keys' => [],
            'active_member_keys' => $activeKeys
                ->map(fn (E2eeMemberKey $key): array => [
                    'id' => $key->id,
                    'user_id' => $key->user_id,
                    'device_label' => $key->device_label,
                    'public_key' => $key->public_key,
                ])
                ->all(),
        ];

        if ($epoch instanceof E2eeKeyEpoch) {
            $envelopesByKey = $this->envelopesForMemberKeys($memberKeys, $epoch)->keyBy('member_key_id');
            $myEnvelope = $envelopesByKey->first();

            $snapshot['team_key_envelope'] = $myEnvelope?->envelope;
            $snapshot['needs_authorization'] = $memberKeys->isNotEmpty() && $myEnvelope === null;

            if ($memberKeys->isNotEmpty()) {
                $snapshot['member_keys'] = $memberKeys
                    ->map(fn (E2eeMemberKey $key): array => $this->memberKeyPayload($key, $team, $envelopesByKey->get($key->id)?->envelope))
                    ->all();
            }
        } else {
            $snapshot['member_keys'] = $memberKeys
                ->map(fn (E2eeMemberKey $key): array => $this->memberKeyPayload($key, $team))
                ->all();
        }

        return $snapshot;
    }

    /**
     * Store wrapped team-key envelopes for an epoch. Only active member keys in
     * the team may be wrapped.
     *
     * @param  array<int, array{public_key: string, envelope: array<string, mixed>}>  $envelopes
     * @return array<string, mixed>
     */
    public function storeWrappedEnvelopes(Team $team, User $user, int $epochId, array $envelopes): array
    {
        $epoch = E2eeKeyEpoch::query()->whereKey($epochId)->where('team_id', $team->id)->first();

        if ($epoch === null) {
            throw new VaultAuthorizationException('Unknown key epoch.');
        }

        $activeKeys = E2eeMemberKey::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->get()
            ->keyBy('public_key');

        DB::transaction(function () use ($epoch, $envelopes, $activeKeys): void {
            foreach ($envelopes as $entry) {
                $publicKey = str_replace('=', '', $entry['public_key']);
                $memberKey = $activeKeys->get($publicKey);

                if ($memberKey === null) {
                    throw new VaultAuthorizationException('Cannot wrap a key for a non-member.');
                }

                E2eeKeyEnvelope::query()->updateOrCreate(
                    ['epoch_id' => $epoch->id, 'member_key_id' => $memberKey->id],
                    ['envelope' => $entry['envelope']],
                );
            }
        });

        $this->logEpochChange($team, $epoch);

        return ['epoch_id' => $epoch->id, 'stored' => count($envelopes)];
    }

    /**
     * Open a new key epoch and retire the current one. Used after member
     * removal so remaining unlocked members can re-wrap to active keys only.
     *
     * @return array<string, int>
     */
    public function openNextEpoch(Team $team, User $user): array
    {
        $locked = DB::transaction(function () use ($team, $user) {
            // Serialize rotation per team to prevent epoch-number collisions.
            $team->newQuery()->whereKey($team->id)->lockForUpdate()->first();

            $current = $this->activeEpoch($team);

            if ($current instanceof E2eeKeyEpoch) {
                $current->update(['status' => E2eeEpochStatus::Retired]);
            }

            $maxEpoch = (int) E2eeKeyEpoch::query()->where('team_id', $team->id)->max('epoch');
            $nextNumber = $maxEpoch + 1;
            $epoch = E2eeKeyEpoch::query()->create([
                'team_id' => $team->id,
                'epoch' => $nextNumber,
                'status' => E2eeEpochStatus::Active,
                'created_by' => $user->id,
            ]);

            $this->logEpochChange($team, $epoch);

            return $epoch;
        });

        return ['epoch_id' => $locked->id, 'epoch' => $locked->epoch];
    }

    /**
     * Rotate by retiring the current epoch on member removal. The removed
     * member's envelopes are dropped because the new epoch has no envelope for
     * them until re-wrapped by remaining members.
     */
    public function retireOnMemberRemoval(Team $team, ?int $removedUserId): void
    {
        DB::transaction(function () use ($team, $removedUserId): void {
            $current = $this->activeEpoch($team);

            if (! $current instanceof E2eeKeyEpoch) {
                return;
            }

            $current->update(['status' => E2eeEpochStatus::Retired]);

            $this->logEpochChange($team, $current);

            if ($removedUserId !== null) {
                $removedKeys = E2eeMemberKey::query()
                    ->where('team_id', $team->id)
                    ->where('user_id', $removedUserId)
                    ->get();

                $removedKeyIds = $removedKeys->pluck('id');

                if ($removedKeyIds->isNotEmpty()) {
                    E2eeKeyEnvelope::query()
                        ->whereIn('member_key_id', $removedKeyIds)
                        ->where('epoch_id', $current->id)
                        ->delete();

                    E2eeMemberKey::query()
                        ->whereIn('id', $removedKeyIds)
                        ->update(['is_active' => false]);
                }
            }
        });
    }

    public function activeEpoch(Team $team): ?E2eeKeyEpoch
    {
        return E2eeKeyEpoch::query()
            ->where('team_id', $team->id)
            ->where('status', E2eeEpochStatus::Active)
            ->orderByDesc('epoch')
            ->first();
    }

    /**
     * @return Collection<int, E2eeMemberKey>
     */
    public function memberKeys(Team $team, User $user): Collection
    {
        return E2eeMemberKey::query()
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('user')
            ->get();
    }

    /**
     * @param  Collection<int, E2eeMemberKey>  $memberKeys
     * @return Collection<int, E2eeKeyEnvelope>
     */
    public function envelopesForMemberKeys(Collection $memberKeys, E2eeKeyEpoch $epoch): Collection
    {
        if ($memberKeys->isEmpty()) {
            return collect();
        }

        return E2eeKeyEnvelope::query()
            ->where('epoch_id', $epoch->id)
            ->whereIn('member_key_id', $memberKeys->pluck('id'))
            ->with('memberKey')
            ->get();
    }

    /**
     * Fetch the wrapped team key envelope for a specific member key on the
     * active epoch, enforcing that the requester is that member.
     *
     * @return array<string, mixed>|null
     */
    public function currentEnvelopeFor(Team $team, User $user, E2eeMemberKey $memberKey): ?array
    {
        if ($memberKey->team_id !== $team->id || $memberKey->user_id !== $user->id) {
            throw new VaultAuthorizationException('Not authorized to fetch this key envelope.');
        }

        $epoch = $this->activeEpoch($team);

        if (! $epoch instanceof E2eeKeyEpoch) {
            return null;
        }

        $envelope = E2eeKeyEnvelope::query()
            ->where('epoch_id', $epoch->id)
            ->where('member_key_id', $memberKey->id)
            ->first();

        return $envelope?->envelope;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>|null  $envelope
     * @return array<string, mixed>
     */
    private function memberKeyPayload(E2eeMemberKey $memberKey, Team $team, ?array $envelope = null): array
    {
        if ($envelope === null) {
            $user = $memberKey->user;

            $envelope = $user instanceof User
                ? $this->currentEnvelopeFor($team, $user, $memberKey)
                : null;
        }

        return [
            'id' => $memberKey->id,
            'device_label' => $memberKey->device_label,
            'public_key' => $memberKey->public_key,
            'is_active' => $memberKey->is_active,
            'team_key_envelope' => $envelope,
            'needs_authorization' => $envelope === null,
        ];
    }

    private function logEpochChange(Team $team, E2eeKeyEpoch $epoch): void
    {
        E2eeSyncLog::query()->create([
            'team_id' => $team->id,
            'change_type' => E2eeChangeType::KeyEpoch->value,
            'epoch_id' => $epoch->id,
            'created_at' => now(),
        ]);
    }
}
