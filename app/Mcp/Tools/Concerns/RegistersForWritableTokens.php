<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Services\McpTokenPermissionService;
use Laravel\Mcp\Request;

trait RegistersForWritableTokens
{
    public function shouldRegister(Request $request): bool
    {
        return app(McpTokenPermissionService::class)->canWriteAnyType();
    }
}
