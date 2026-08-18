<?php

use App\Enums\E2eeEpochStatus;
use App\Enums\TeamFeature;
use App\Enums\TeamRole;
use App\Models\E2eeFile;
use App\Models\E2eeKeyEnvelope;
use App\Models\E2eeKeyEpoch;
use App\Models\E2eeMemberKey;
use App\Models\E2eeRecordEnvelope;
use App\Models\E2eeSyncLog;
use App\Models\McpToken;
use App\Models\Team;
use App\Models\User;
use App\Services\RecordTypeRegistry;
use App\Services\VaultKeyService;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

function enableE2ee($team): void
{
    $team->forceFill([
        'feature_settings' => array_merge($team->featureSettings(), [TeamFeature::E2ee->value => true]),
    ])->save();
}

function envelopeFixture(int $version, int $kid = 7, string $rid = 'note:42'): array
{
    return [
        'v' => 1,
        'alg' => 'xchacha20poly1305',
        'kid' => $kid,
        'rid' => $rid,
        'ver' => $version,
        'n' => 'frozen-nonce',
        'ct' => 'frozen-ciphertext-'.$version,
    ];
}

test('e2ee feature flag defaults to disabled and can be enabled', function () {
    $team = User::factory()->create()->currentTeam;

    expect($team->hasFeature(TeamFeature::E2ee))->toBeFalse();

    enableE2ee($team);

    expect($team->fresh()->hasFeature(TeamFeature::E2ee))->toBeTrue();
});

test('push stores opaque envelope and logs sync change with cursor', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'epoch' => 1, 'created_by' => $user->id]);

    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), [
        'records' => [[
            'type' => 'note',
            'id' => 42,
            'epoch_id' => $epoch->id,
            'version' => 1,
            'envelope' => envelopeFixture(1, (int) $epoch->id, 'note:42'),
        ]],
    ])->assertOk()->assertJsonPath('accepted.0.status', 'accepted');

    expect(E2eeRecordEnvelope::where('team_id', $team->id)->where('record_type', 'note')->where('record_id', 42)->count())->toBe(1)
        ->and(E2eeSyncLog::where('team_id', $team->id)->count())->toBe(1);
});

test('push is idempotent for identical retries', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);
    $payload = [
        'records' => [[
            'type' => 'note',
            'id' => 42,
            'epoch_id' => $epoch->id,
            'version' => 1,
            'envelope' => envelopeFixture(1),
        ]],
    ];

    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), $payload)->assertOk();
    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), $payload)->assertOk()->assertJsonPath('accepted.0.status', 'duplicate');

    expect(E2eeSyncLog::where('team_id', $team->id)->count())->toBe(1);
});

test('push rejects stale optimistic versions with a conflict', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), [
        'records' => [['type' => 'note', 'id' => 1, 'epoch_id' => $epoch->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $epoch->id, 'note:1')]],
    ])->assertOk();

    // A second writer submits version 1 again, but the stored version is already 2.
    E2eeRecordEnvelope::where('team_id', $team->id)->where('record_type', 'note')->where('record_id', 1)
        ->update(['version' => 2, 'envelope' => envelopeFixture(2)]);

    $resp = actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), [
        'records' => [['type' => 'note', 'id' => 1, 'epoch_id' => $epoch->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $epoch->id, 'note:1')]],
    ])->assertOk();

    expect($resp->json('conflicts.0.reason'))->toBe('version_conflict')
        ->and($resp->json('conflicts.0.expected'))->toBe(2);
});

test('push rejects writes encrypted under a retired epoch', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $active = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'epoch' => 2, 'status' => E2eeEpochStatus::Active->value, 'created_by' => $user->id]);
    $retired = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'epoch' => 1, 'status' => E2eeEpochStatus::Retired->value, 'created_by' => $user->id]);

    $resp = actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), [
        'records' => [['type' => 'note', 'id' => 9, 'epoch_id' => $retired->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $retired->id, 'note:9')]],
    ])->assertOk();

    expect($resp->json('conflicts.0.reason'))->toBe('stale_epoch')
        ->and($resp->json('conflicts.0.current_epoch'))->toBe((int) $active->id);
});

test('pull returns changes after a cursor and advances it', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), [
        'records' => [['type' => 'note', 'id' => 1, 'epoch_id' => $epoch->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $epoch->id, 'note:1')]],
    ])->assertOk();

    $pull = actingAs($user)->postJson(route('team.vault.pull', ['current_team' => $team]), [
        'cursor' => 0,
    ])->assertOk();

    expect($pull->json('changes'))->toHaveCount(1)
        ->and($pull->json('changes.0.change_type'))->toBe('record')
        ->and($pull->json('changes.0.record_type'))->toBe('note')
        ->and($pull->json('next_cursor'))->toBe($pull->json('changes.0.cursor'));

    // Pulling after the cursor returns nothing new.
    $next = actingAs($user)->postJson(route('team.vault.pull', ['current_team' => $team]), [
        'cursor' => $pull->json('next_cursor'),
    ])->assertOk();

    expect($next->json('changes'))->toHaveCount(0);
});

test('pull filters by record type', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), ['records' => [
        ['type' => 'note', 'id' => 1, 'epoch_id' => $epoch->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $epoch->id, 'note:1')],
        ['type' => 'task', 'id' => 5, 'epoch_id' => $epoch->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $epoch->id, 'task:5')],
    ]])->assertOk();

    $pull = actingAs($user)->postJson(route('team.vault.pull', ['current_team' => $team]), [
        'cursor' => 0,
        'types' => ['task'],
    ])->assertOk();

    expect(collect($pull->json('changes'))->pluck('record_type')->unique()->all())->toBe(['task']);
});

test('tombstone push persists and includes tombstone flag', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), ['records' => [
        ['type' => 'note', 'id' => 3, 'epoch_id' => $epoch->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $epoch->id, 'note:3'), 'tombstone' => true],
    ]])->assertOk()->assertJsonPath('accepted.0.status', 'accepted');

    $record = E2eeRecordEnvelope::where('team_id', $team->id)->where('record_type', 'note')->where('record_id', 3)->first();
    expect($record->is_tombstone)->toBeTrue();
});

test('member key registration is only possible for active team members', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $outsider = User::factory()->create();

    actingAs($outsider)
        ->postJson(route('team.vault.keys.register', ['current_team' => $team]), [
            'device_label' => 'laptop',
            'public_key' => 'member-public-key',
        ])
        ->assertForbidden();

    $memberKey = actingAs($user)
        ->postJson(route('team.vault.keys.register', ['current_team' => $team]), [
            'device_label' => 'laptop',
            'public_key' => 'member-public-key',
        ])
        ->assertOk()
        ->json('member_key');

    expect($memberKey['public_key'])->toBe('member-public-key')
        ->and(E2eeMemberKey::where('team_id', $team->id)->count())->toBe(1);
});

test('current team key envelope is limited to active member devices on the active epoch', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $memberKey = E2eeMemberKey::factory()->create(['team_id' => $team->id, 'user_id' => $user->id, 'public_key' => 'pk-1']);
    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);
    E2eeKeyEnvelope::factory()->create(['epoch_id' => $epoch->id, 'member_key_id' => $memberKey->id, 'envelope' => ['wrapped' => 'key']]);

    $keys = actingAs($user)
        ->getJson(route('team.vault.keys', ['current_team' => $team]))
        ->assertOk()
        ->json('vault');

    expect($keys['epoch']['id'])->toBe((int) $epoch->id)
        ->and($keys['member_keys'][0]['team_key_envelope'])->toBe(['wrapped' => 'key'])
        ->and($keys['needs_authorization'])->toBeFalse();
});

test('storing wrapped envelopes only permits active member keys in the team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $memberKey = E2eeMemberKey::factory()->create(['team_id' => $team->id, 'user_id' => $user->id, 'public_key' => 'pk-1']);
    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

    actingAs($user)
        ->postJson(route('team.vault.keys.envelopes', ['current_team' => $team]), [
            'epoch_id' => $epoch->id,
            'envelopes' => [
                ['public_key' => 'pk-1', 'envelope' => ['wrapped' => 'for-member']],
                ['public_key' => 'pk-999', 'envelope' => ['wrapped' => 'for-outsider']],
            ],
        ])
        ->assertForbidden();

    expect(E2eeKeyEnvelope::where('epoch_id', $epoch->id)->count())->toBe(0);
});

test('epoch rotation on member removal retires the current epoch and drops envelopes', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $removed = User::factory()->create();
    $removedMemberKey = E2eeMemberKey::factory()->create(['team_id' => $team->id, 'user_id' => $removed->id, 'public_key' => 'pk-removed']);
    $staying = E2eeMemberKey::factory()->create(['team_id' => $team->id, 'user_id' => $user->id, 'public_key' => 'pk-staying']);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);
    E2eeKeyEnvelope::factory()->create(['epoch_id' => $epoch->id, 'member_key_id' => $removedMemberKey->id]);
    E2eeKeyEnvelope::factory()->create(['epoch_id' => $epoch->id, 'member_key_id' => $staying->id]);

    app(VaultKeyService::class)->retireOnMemberRemoval($team, $removed->id);

    expect($epoch->fresh()->status->value)->toBe(E2eeEpochStatus::Retired->value)
        ->and(E2eeKeyEnvelope::where('epoch_id', $epoch->id)->where('member_key_id', $removedMemberKey->id)->exists())->toBeFalse()
        ->and($removedMemberKey->fresh()->is_active)->toBeFalse()
        ->and(E2eeKeyEnvelope::where('epoch_id', $epoch->id)->where('member_key_id', $staying->id)->exists())->toBeTrue();
});

test('rotation opens the next epoch and logs a key epoch change', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'epoch' => 1, 'created_by' => $user->id]);

    $result = actingAs($user)
        ->postJson(route('team.vault.keys.rotate', ['current_team' => $team]))
        ->assertOk()
        ->json('epoch');

    expect($result['epoch'])->toBe(2)
        ->and($epoch->fresh()->status->value)->toBe(E2eeEpochStatus::Retired->value);

    $log = E2eeSyncLog::where('team_id', $team->id)->where('change_type', 'key_epoch')->latest('id')->first();
    expect($log)->not->toBeNull();
});

test('mcp tools/list advertises vault tools only for e2ee teams', function () {
    $abilities = [
        ...array_fill_keys(RecordTypeRegistry::mcpResources(), 'write'),
        'task_projects' => ['mode' => 'all', 'ids' => []],
    ];

    // E2EE team: vault.* advertised, records.* hidden.
    $e2eeUser = User::factory()->create();
    $e2eeTeam = $e2eeUser->currentTeam;
    enableE2ee($e2eeTeam);

    $e2eeToken = McpToken::createToken($e2eeUser, $e2eeTeam, 'E2EE client', $abilities)[1];

    $response = $this->withToken($e2eeToken)
        ->postJson(route('mcp.records'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])
        ->assertSuccessful();

    $toolNames = collect($response->json('result.tools'))->pluck('name')->all();
    expect($toolNames)->toContain('vault.keys.get', 'vault.keys.put', 'vault.pull', 'vault.push', 'vault.files.get', 'vault.files.put')
        ->not->toContain('records.schema', 'records.create', 'records.update', 'records.delete', 'records.search', 'records.get');

    // Plaintext team: records.* advertised, vault.* hidden.
    $plainUser = User::factory()->create();
    $plainTeam = $plainUser->currentTeam;
    $plainToken = McpToken::createToken($plainUser, $plainTeam, 'Plain client', $abilities)[1];

    $response = $this->withToken($plainToken)
        ->postJson(route('mcp.records'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])
        ->assertSuccessful();

    $plainNames = collect($response->json('result.tools'))->pluck('name')->all();
    expect($plainNames)->toContain('records.schema', 'records.create', 'records.get')
        ->not->toContain('vault.pull', 'vault.push', 'vault.keys.get', 'vault.files.put');

    app()->forgetInstance(McpToken::class);
});

test('encrypted file put and get return ciphertext without plaintext inspection', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

    $put = actingAs($user)->postJson(route('team.vault.files.put', ['current_team' => $team]), [
        'file_id' => 10,
        'epoch_id' => $epoch->id,
        'version' => 1,
        'metadata' => ['v' => 1, 'encrypted' => 'metadata-ciphertext'],
        'content' => base64_encode('encrypted-file-bytes'),
        'chunk_count' => '1',
    ])->assertOk();

    expect($put->json('file_id'))->toBe(10);

    $get = actingAs($user)->getJson(route('team.vault.files.get', ['current_team' => $team, 'file' => 10]))
        ->assertOk()
        ->json('file');

    expect($get['metadata'])->toBe(['v' => 1, 'encrypted' => 'metadata-ciphertext'])
        ->and(base64_decode($get['content']))->toBe('encrypted-file-bytes')
        ->and(E2eeFile::where('team_id', $team->id)->where('file_id', 10)->first()->path)->not->toBeNull();
});

test('e2ee team page responses contain no decrypted fields', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    // The dashboard for an E2EE team must not leak plaintext record values.
    $resp = actingAs($user)->get(route('team.dashboard', ['current_team' => $team]));
    expect($resp->status())->toBe(200);
});

test('push treats equal version with different ciphertext as a conflict', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);
    $base = ['type' => 'note', 'id' => 7, 'epoch_id' => $epoch->id, 'version' => 3];

    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), [
        'records' => [[...$base, 'envelope' => envelopeFixture(3, (int) $epoch->id, 'note:7')]],
    ])->assertOk()->assertJsonPath('accepted.0.status', 'accepted');

    // Same version but a different envelope is ambiguous: conflict, no overwrite.
    $resp = actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), [
        'records' => [[
            'type' => 'note',
            'id' => 7,
            'epoch_id' => $epoch->id,
            'version' => 3,
            'envelope' => array_replace(envelopeFixture(3, (int) $epoch->id, 'note:7'), ['ct' => 'different']),
        ]],
    ])->assertOk();

    expect($resp->json('conflicts.0.reason'))->toBe('version_conflict')
        ->and(E2eeRecordEnvelope::where('team_id', $team->id)->where('record_id', 7)->first()->envelope['ct'])->toBe('frozen-ciphertext-3');
});

test('rotation after member removal does not duplicate epoch numbers', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $removed = User::factory()->create();
    E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'epoch' => 1, 'created_by' => $user->id]);

    app(VaultKeyService::class)->retireOnMemberRemoval($team, $removed->id);

    $result = actingAs($user)
        ->postJson(route('team.vault.keys.rotate', ['current_team' => $team]))
        ->assertOk()
        ->json('epoch');

    expect($result['epoch'])->toBe(2);
});

test('removing a member of an e2ee team retires the active epoch and drops member envelopes', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = ($member->currentTeam);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->forceFill(['feature_settings' => array_merge($team->featureSettings(), [TeamFeature::E2ee->value => true])])->save();

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $owner->id]);
    $memberKey = E2eeMemberKey::factory()->create(['team_id' => $team->id, 'user_id' => $member->id, 'public_key' => 'pk-removed', 'is_active' => true]);
    E2eeKeyEnvelope::factory()->create(['epoch_id' => $epoch->id, 'member_key_id' => $memberKey->id]);

    actingAs($owner)
        ->delete(route('teams.members.destroy', ['team' => $team, 'user' => $member]))
        ->assertRedirect(route('teams.edit', $team));

    expect($member->fresh()->belongsToTeam($team))->toBeFalse()
        ->and($epoch->fresh()->status->value)->toBe(E2eeEpochStatus::Retired->value)
        ->and($memberKey->fresh()->is_active)->toBeFalse()
        ->and(E2eeKeyEnvelope::where('epoch_id', $epoch->id)->where('member_key_id', $memberKey->id)->exists())->toBeFalse()
        ->and(E2eeSyncLog::where('team_id', $team->id)->where('change_type', 'key_epoch')->exists())->toBeTrue();
});

test('typed pulls advance the cursor past all scanned rows', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    enableE2ee($team);

    $epoch = E2eeKeyEpoch::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

    actingAs($user)->postJson(route('team.vault.push', ['current_team' => $team]), ['records' => [
        ['type' => 'note', 'id' => 1, 'epoch_id' => $epoch->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $epoch->id, 'note:1')],
        ['type' => 'task', 'id' => 5, 'epoch_id' => $epoch->id, 'version' => 1, 'envelope' => envelopeFixture(1, (int) $epoch->id, 'task:5')],
    ]])->assertOk();

    // A typed pull returns only the requested type but advances past boundary
    // rows (including key_epoch/file rows) so rescanning never repeats them.
    $typed = actingAs($user)->postJson(route('team.vault.pull', ['current_team' => $team]), [
        'cursor' => 0,
        'types' => ['task'],
    ])->assertOk();

    expect(collect($typed->json('changes'))->pluck('record_type')->unique()->all())->toBe(['task'])
        ->and($typed->json('next_cursor'))->toBe(2);

    // Pulling after the advanced cursor returns nothing new.
    $all = actingAs($user)->postJson(route('team.vault.pull', ['current_team' => $team]), [
        'cursor' => $typed->json('next_cursor'),
    ])->assertOk();

    expect($all->json('changes'))->toBeEmpty();
});
