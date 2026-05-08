<?php

declare(strict_types=1);

use App\Mcp\Servers\RecordsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/records', RecordsServer::class)
    ->name('mcp.records')
    ->middleware(['auth', 'verified', 'throttle:60,1']);
