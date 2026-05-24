<?php

use App\Models\McpToken;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\McpTokenPermissionService;

beforeEach(function () {
    app()->forgetInstance(McpToken::class);
});

afterEach(function () {
    app()->forgetInstance(McpToken::class);
});

it('allows all actions when no token is bound', function () {
    $service = app(McpTokenPermissionService::class);

    expect($service->can('task', 'read'))->toBeTrue()
        ->and($service->can('task', 'write'))->toBeTrue()
        ->and($service->can('note', 'read'))->toBeTrue()
        ->and($service->can('note', 'write'))->toBeTrue();
});

it('delegates to token allows when token is bound', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $token = McpToken::createToken($user, $team, 'Test', [
        'notes' => 'read',
        'tasks' => 'write',
        'bookmarks' => 'none',
        'contacts' => 'none',
        'calendar' => 'none',
        'collections' => 'none',
        'log_entries' => 'none',
        'task_projects' => ['mode' => 'all', 'ids' => []],
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);

    expect($service->can('note', 'read'))->toBeTrue()
        ->and($service->can('note', 'write'))->toBeFalse()
        ->and($service->can('task', 'write'))->toBeTrue()
        ->and($service->can('bookmark', 'read'))->toBeFalse();
});

it('returns all types as readable when no token is bound', function () {
    $service = app(McpTokenPermissionService::class);

    $readable = $service->readableTypes();

    expect($readable)->toContain('task', 'note', 'bookmark', 'contact', 'calendar_event', 'collection', 'subscription', 'log_entry');
});

it('filters readable types based on token abilities', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $token = McpToken::createToken($user, $team, 'Test', [
        'notes' => 'read',
        'tasks' => 'write',
        'bookmarks' => 'none',
        'contacts' => 'none',
        'calendar' => 'none',
        'collections' => 'none',
        'log_entries' => 'none',
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);
    $readable = $service->readableTypes();

    expect($readable)->toContain('task', 'note')
        ->and($readable)->not->toContain('bookmark', 'contact', 'calendar_event', 'collection', 'log_entry');
});

it('filters readable types based on team features', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->update(['feature_settings' => array_replace($team->featureSettings(), ['contacts' => false])]);

    $token = McpToken::createToken($user, $team, 'Test', [
        'contacts' => 'read',
        'tasks' => 'read',
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);

    expect($service->readableTypes())->toContain('task')
        ->not->toContain('contact');
});

it('returns all types as writable when no token is bound', function () {
    $service = app(McpTokenPermissionService::class);

    $writable = $service->writableTypes();

    expect($writable)->toContain('task', 'note', 'bookmark', 'contact', 'calendar_event', 'collection', 'subscription', 'log_entry');
});

it('filters writable types to write-level only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $token = McpToken::createToken($user, $team, 'Test', [
        'notes' => 'read',
        'tasks' => 'write',
        'bookmarks' => 'none',
        'contacts' => 'write',
        'calendar' => 'none',
        'collections' => 'none',
        'log_entries' => 'none',
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);
    $writable = $service->writableTypes();

    expect($writable)->toContain('task', 'contact')
        ->and($writable)->not->toContain('note', 'bookmark', 'calendar_event', 'collection', 'log_entry');
});

it('returns true for canWriteAnyType when no token is bound', function () {
    $service = app(McpTokenPermissionService::class);

    expect($service->canWriteAnyType())->toBeTrue();
});

it('returns false for canWriteAnyType when token has no write permissions', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $token = McpToken::createToken($user, $team, 'Read-only', [
        'notes' => 'read',
        'tasks' => 'read',
        'bookmarks' => 'read',
        'contacts' => 'read',
        'calendar' => 'read',
        'collections' => 'read',
        'log_entries' => 'read',
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);

    expect($service->canWriteAnyType())->toBeFalse();
});

it('returns false for can with invalid type', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $token = McpToken::createToken($user, $team, 'Test', [
        'notes' => 'write',
        'tasks' => 'write',
        'bookmarks' => 'write',
        'contacts' => 'write',
        'calendar' => 'write',
        'collections' => 'write',
        'log_entries' => 'write',
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);

    expect($service->can('invalid_type', 'read'))->toBeFalse();
});

it('returns unchanged payload from filterPayload when no token is bound', function () {
    $service = app(McpTokenPermissionService::class);
    $payload = ['id' => 1, 'title' => 'Test', 'related' => [['type' => 'note', 'id' => 2]]];

    expect($service->filterPayload($payload))->toBe($payload);
});

it('returns unchanged payload from filterPayload when no related key exists', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $token = McpToken::createToken($user, $team, 'Test', [
        'notes' => 'write',
        'tasks' => 'write',
        'bookmarks' => 'write',
        'contacts' => 'write',
        'calendar' => 'write',
        'collections' => 'write',
        'log_entries' => 'write',
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);
    $payload = ['id' => 1, 'title' => 'Test'];

    expect($service->filterPayload($payload))->toBe($payload);
});

it('filters related records based on token read permissions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id])->id,
    ]);

    $token = McpToken::createToken($user, $team, 'Test', [
        'notes' => 'read',
        'tasks' => 'none',
        'bookmarks' => 'none',
        'contacts' => 'none',
        'calendar' => 'none',
        'collections' => 'none',
        'log_entries' => 'none',
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);
    $payload = [
        'id' => 1,
        'related' => [
            ['type' => 'note', 'id' => $note->id],
            ['type' => 'task', 'id' => $task->id],
        ],
    ];

    $result = $service->filterPayload($payload);

    expect($result['related'])->toHaveCount(1)
        ->and($result['related'][0]['type'])->toBe('note');
});

it('respects task project scope in permission checks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $allowedProject = Project::factory()->create(['team_id' => $team->id]);
    $blockedProject = Project::factory()->create(['team_id' => $team->id]);
    $token = McpToken::createToken($user, $team, 'Scoped', [
        'tasks' => 'write',
        'task_projects' => ['mode' => 'only', 'ids' => [$allowedProject->id]],
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);

    expect($service->can('task', 'write', null, ['project_id' => $allowedProject->id]))->toBeTrue()
        ->and($service->can('task', 'write', null, ['project_id' => $blockedProject->id]))->toBeFalse();
});

it('does not apply project scope to non-task types', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $token = McpToken::createToken($user, $team, 'Scoped', [
        'notes' => 'write',
        'task_projects' => ['mode' => 'only', 'ids' => [999]],
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);

    expect($service->can('note', 'write'))->toBeTrue();
});

it('handles subscription type in permission checks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $token = McpToken::createToken($user, $team, 'Sub test', [
        'subscriptions' => 'write',
        'notes' => 'none',
        'tasks' => 'read',
        'bookmarks' => 'none',
        'contacts' => 'none',
        'calendar' => 'none',
        'collections' => 'none',
        'log_entries' => 'none',
    ])[0];

    app()->instance(McpToken::class, $token);

    $service = app(McpTokenPermissionService::class);

    expect($service->can('subscription', 'write'))->toBeTrue()
        ->and($service->can('subscription', 'read'))->toBeTrue()
        ->and($service->readableTypes())->toContain('subscription', 'task')
        ->and($service->writableTypes())->toContain('subscription')
        ->and($service->writableTypes())->not->toContain('task');
});
