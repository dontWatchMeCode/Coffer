<?php

use App\Models\User;
use App\Services\RecordTypeRegistry;

it('shows every registry-derived MCP permission', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/mcp')
        ->click('[title="Create MCP"]');

    foreach (RecordTypeRegistry::mcpResources() as $resource) {
        $page->assertPresent('[data-testid="mcp-permission-'.$resource.'"]');
    }

    $page->assertNoJavaScriptErrors();
});
