<?php

use App\Rules\TeamName;

function validateTeamName(string $value): bool
{
    $rule = new TeamName;
    $failed = false;

    $rule->validate('name', $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

it('rejects names that collide with real top-level route prefixes', function (string $name) {
    expect(validateTeamName($name))->toBeTrue();
})->with([
    'settings' => ['settings'],
    'dashboard' => ['dashboard'],
    'invitations' => ['invitations'],
    'mcp' => ['mcp'],
]);

it('rejects the fixed reserved application names', function (string $name) {
    expect(validateTeamName($name))->toBeTrue();
})->with([
    'api' => ['api'],
    'API' => ['API'],
    'Mcp' => ['Mcp'],
]);

it('rejects reserved names regardless of casing or surrounding whitespace', function () {
    expect(validateTeamName('  Settings  '))->toBeTrue()
        ->and(validateTeamName('Dashboard'))->toBeTrue();
});

it('allows ordinary team names that do not collide with routes', function (string $name) {
    expect(validateTeamName($name))->toBeFalse();
})->with([
    'acme' => ['acme'],
    'team-alpha' => ['team-alpha'],
    'my company' => ['my company'],
    'bookmarks' => ['bookmarks'],
    'tasks' => ['tasks'],
]);
