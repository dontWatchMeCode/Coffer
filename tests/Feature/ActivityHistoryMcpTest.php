<?php

use App\Concerns\ProvidesActivityHistory;
use App\Models\McpToken;
use App\Models\Note;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    app()->forgetInstance(McpToken::class);
});

afterEach(function () {
    app()->forgetInstance(McpToken::class);
});

test('activity logged during mcp request includes token name in properties', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Original']);

    $token = McpToken::createToken($user, $team, 'Claude Desktop', [
        'notes' => 'write',
    ])[0];

    app()->instance(McpToken::class, $token);

    $note->update(['title' => 'Updated by MCP']);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'updated')
        ->orderByDesc('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('mcp_token_name'))->toBe('Claude Desktop');
});

test('activity not from mcp request does not include token name', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Original']);

    $note->update(['title' => 'Updated by user']);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'updated')
        ->orderByDesc('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('mcp_token_name'))->toBeNull();
});

test('activity history payload shows mcp token name when causer was null', function () {
    $trait = new class
    {
        use ProvidesActivityHistory;

        public function build(Activity $activity): array
        {
            return $this->buildActivityItem($activity);
        }
    };

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Original']);

    $token = McpToken::createToken($user, $team, 'Claude Desktop', [
        'notes' => 'write',
    ])[0];

    app()->instance(McpToken::class, $token);

    $note->update(['title' => 'Updated']);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'updated')
        ->with('causer')
        ->orderByDesc('id')
        ->first();
    $item = $trait->build($activity);

    expect($item['causerName'])->toBe('MCP Claude Desktop');
});

test('activity history payload shows mcp prefix when causer exists', function () {
    $trait = new class
    {
        use ProvidesActivityHistory;

        public function build(Activity $activity): array
        {
            return $this->buildActivityItem($activity);
        }
    };

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Original']);

    $token = McpToken::createToken($user, $team, 'My Bot', [
        'notes' => 'write',
    ])[0];

    $this->actingAs($user);
    app()->instance(McpToken::class, $token);

    $note->update(['title' => 'Updated']);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'updated')
        ->with('causer')
        ->orderByDesc('id')
        ->first();
    $item = $trait->build($activity);

    expect($item['causerName'])->toBe('MCP My Bot');
});

test('activity history payload shows user name when not mcp', function () {
    $trait = new class
    {
        use ProvidesActivityHistory;

        public function build(Activity $activity): array
        {
            return $this->buildActivityItem($activity);
        }
    };

    $user = User::factory()->create(['name' => 'Jane Doe']);
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Original']);

    $this->actingAs($user);
    $note->update(['title' => 'Updated']);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'updated')
        ->with('causer')
        ->orderByDesc('id')
        ->first();
    $item = $trait->build($activity);

    expect($item['causerName'])->toBe('Jane Doe');
});
