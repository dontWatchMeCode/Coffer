<?php

use App\Enums\TeamRole;
use App\Models\McpToken;
use App\Models\Project;
use App\Models\User;
use App\Services\RecordTypeRegistry;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function apiTokenTestAbilities(array $overrides = []): array
{
    $taskProjects = $overrides['task_projects'] ?? ['mode' => 'all', 'ids' => []];
    unset($overrides['task_projects']);

    return [
        ...array_fill_keys(RecordTypeRegistry::mcpResources(), 'none'),
        ...$overrides,
        'task_projects' => $taskProjects,
    ];
}

test('api tokens page can be rendered for team members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;

    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    actingAs($member)
        ->get(route('team.mcp.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('api-tokens/Index')
            ->has('tokens')
            ->has('projects'),
        );
});

test('team members can create and revoke mcp tokens', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->post(route('team.mcp.store', ['current_team' => $team]), [
            'name' => 'OpenCode',
            'abilities' => apiTokenTestAbilities([
                'collections' => 'read',
                'notes' => 'write',
                'bookmarks' => 'none',
                'subscriptions' => 'none',
                'contacts' => 'read',
                'calendar' => 'none',
                'tasks' => 'write',
                'files' => 'write',
                'log_entries' => 'read',
                'task_projects' => ['mode' => 'only', 'ids' => [$project->id]],
            ]),
        ])
        ->assertRedirect();

    $token = McpToken::query()->where('name', 'OpenCode')->firstOrFail();

    expect($token->team_id)->toBe($team->id)
        ->and($token->user_id)->toBe($user->id)
        ->and($token->abilities['notes'])->toBe('write')
        ->and($token->abilities['files'])->toBe('write')
        ->and($token->abilities['log_entries'])->toBe('read')
        ->and($token->abilities['task_projects']['ids'])->toBe([$project->id]);

    actingAs($user)
        ->delete(route('team.mcp.destroy', ['current_team' => $team, 'mcpToken' => $token]))
        ->assertRedirect();

    expect(McpToken::query()->whereKey($token->id)->exists())->toBeFalse();
});

test('team members can edit mcp tokens', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    $token = McpToken::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'name' => 'Original',
        'abilities' => apiTokenTestAbilities(),
    ]);

    actingAs($user)
        ->patch(route('team.mcp.update', ['current_team' => $team, 'mcpToken' => $token]), [
            'name' => 'Updated',
            'abilities' => apiTokenTestAbilities([
                'collections' => 'read',
                'notes' => 'write',
                'bookmarks' => 'none',
                'subscriptions' => 'none',
                'contacts' => 'read',
                'calendar' => 'none',
                'tasks' => 'write',
                'files' => 'read',
                'log_entries' => 'write',
                'task_projects' => ['mode' => 'only', 'ids' => [$project->id]],
            ]),
        ])
        ->assertRedirect();

    $token->refresh();

    expect($token->name)->toBe('Updated')
        ->and($token->abilities['notes'])->toBe('write')
        ->and($token->abilities['files'])->toBe('read')
        ->and($token->abilities['log_entries'])->toBe('write')
        ->and($token->abilities['task_projects']['ids'])->toBe([$project->id]);
});

it('validates every registry-derived MCP permission', function (string $resource) {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.mcp.store', ['current_team' => $team]), [
            'name' => 'Invalid token',
            'abilities' => apiTokenTestAbilities([$resource => 'invalid']),
        ])
        ->assertSessionHasErrors("abilities.{$resource}");
})->with(RecordTypeRegistry::mcpResources());

test('api tokens page supports legacy tokens without plaintext values', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    McpToken::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'name' => 'Legacy token',
        'token' => null,
        'abilities' => [
            'tasks' => 'read',
            'notes' => 'admin',
        ],
    ]);

    actingAs($user)
        ->get(route('team.mcp.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('api-tokens/Index')
            ->has('tokens', 1, fn (Assert $page) => $page
                ->where('name', 'Legacy token')
                ->where('token', null)
                ->where('abilities.tasks', 'read')
                ->where('abilities.notes', 'none')
                ->where('abilities.files', 'none')
                ->where('abilities.log_entries', 'none')
                ->where('abilities.task_projects.mode', 'all')
                ->where('abilities.task_projects.ids', [])
                ->etc(),
            )
            ->has('projects')
            ->has('permissionLevels')
            ->where('resourceLabels', RecordTypeRegistry::mcpResourceLabels())
            ->where('mcpEndpointUrl', route('mcp.records')),
        );
});

it('keeps unknown legacy task scope modes restricted when presenting tokens', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    McpToken::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'abilities' => [
            'tasks' => 'read',
            'task_projects' => ['mode' => 'legacy', 'ids' => [123]],
        ],
    ]);

    actingAs($user)
        ->get(route('team.mcp.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tokens.0.abilities.task_projects.mode', 'only')
            ->where('tokens.0.abilities.task_projects.ids', [123]),
        );
});
