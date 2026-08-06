<?php

declare(strict_types=1);

use App\Enums\TeamFeature;
use App\Enums\TeamRole;
use App\Models\McpToken;
use App\Services\RecordTypeRegistry;

/**
 * The frontend mirrors these lists as literal unions and constants so they can be typed.
 * These tests fail when the PHP side gains or reorders an entry the frontend never learned about.
 */
function frontendSource(string $path): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/resources/js/'.$path);
}

/**
 * @return list<string>
 */
function unionMembers(string $source, string $type): array
{
    preg_match('/export type '.$type.' =(.*?);/s', $source, $union);
    preg_match_all("/'([a-z_]+)'/", $union[1] ?? '', $members);

    return $members[1];
}

it('keeps the MCP resource union in sync with the registry', function () {
    expect(unionMembers(frontendSource('types/api-tokens.ts'), 'ApiTokenResource'))
        ->toBe(RecordTypeRegistry::mcpResources());
});

it('keeps the team feature union in sync with the enum', function () {
    expect(unionMembers(frontendSource('types/teams.ts'), 'TeamFeatureKey'))
        ->toBe(TeamFeature::values());
});

it('keeps the search categories in sync with the registry', function () {
    preg_match_all(
        "/key: '([a-z_]+)',\s+label: '([^']*)',\s+prefix: '([a-z])',/",
        frontendSource('lib/search.ts'),
        $categories,
        PREG_SET_ORDER,
    );

    $expected = collect(RecordTypeRegistry::definitions())
        ->mapWithKeys(fn (array $definition): array => [
            $definition['global'] => ['label' => $definition['label'], 'prefix' => $definition['prefix']],
        ])
        ->all();

    expect(collect($categories)
        ->mapWithKeys(fn (array $category): array => [
            $category[1] => ['label' => $category[2], 'prefix' => $category[3]],
        ])
        ->all())
        ->toBe($expected);
});

it('keeps the permission level union in sync with the token model', function () {
    expect(unionMembers(frontendSource('types/api-tokens.ts'), 'ApiTokenPermission'))
        ->toBe(McpToken::PERMISSION_LEVELS);
});

it('keeps the team role union in sync with the enum', function () {
    expect(unionMembers(frontendSource('types/teams.ts'), 'TeamRole'))
        ->toBe(array_column(TeamRole::cases(), 'value'));
});
