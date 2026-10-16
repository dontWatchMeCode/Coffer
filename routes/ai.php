<?php

declare(strict_types=1);

use App\Http\Middleware\AuthenticateMcpToken;
use App\Http\Middleware\SetTeamUrlDefaults;
use App\Mcp\Servers\RecordsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/records', RecordsServer::class)
    ->name('mcp.records')
    // Mcp::web() skips the web group, so reset team URL defaults explicitly.
    ->middleware([AuthenticateMcpToken::class, 'throttle:60,1', SetTeamUrlDefaults::class]);
