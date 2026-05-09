<?php

use App\Enums\TeamRole;
use App\Models\McpToken;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

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
            'abilities' => [
                'collections' => 'read',
                'notes' => 'write',
                'bookmarks' => 'none',
                'contacts' => 'read',
                'calendar' => 'none',
                'tasks' => 'write',
                'task_projects' => ['mode' => 'only', 'ids' => [$project->id]],
            ],
        ])
        ->assertRedirect();

    $token = McpToken::query()->where('name', 'OpenCode')->firstOrFail();

    expect($token->team_id)->toBe($team->id)
        ->and($token->user_id)->toBe($user->id)
        ->and($token->abilities['notes'])->toBe('write')
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
        'abilities' => [
            'collections' => 'none',
            'notes' => 'none',
            'bookmarks' => 'none',
            'contacts' => 'none',
            'calendar' => 'none',
            'tasks' => 'none',
            'task_projects' => ['mode' => 'all', 'ids' => []],
        ],
    ]);

    actingAs($user)
        ->patch(route('team.mcp.update', ['current_team' => $team, 'mcpToken' => $token]), [
            'name' => 'Updated',
            'abilities' => [
                'collections' => 'read',
                'notes' => 'write',
                'bookmarks' => 'none',
                'contacts' => 'read',
                'calendar' => 'none',
                'tasks' => 'write',
                'task_projects' => ['mode' => 'only', 'ids' => [$project->id]],
            ],
        ])
        ->assertRedirect();

    $token->refresh();

    expect($token->name)->toBe('Updated')
        ->and($token->abilities['notes'])->toBe('write')
        ->and($token->abilities['task_projects']['ids'])->toBe([$project->id]);
});
