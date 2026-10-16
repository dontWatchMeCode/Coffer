<?php

declare(strict_types=1);

use App\Http\Middleware\AuthenticateMcpToken;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\SetTeamUrlDefaults;
use App\Mcp\Servers\RecordsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/records', RecordsServer::class)
    ->name('mcp.records')
    // Mcp::web() does not apply the `web` group, so per-request resets for
    // URL defaults and appearance shares run here instead of leaking the
    // previous worker request's values into tool payloads/error views.
    ->middleware([AuthenticateMcpToken::class, 'throttle:60,1', SetTeamUrlDefaults::class, HandleAppearance::class]);
