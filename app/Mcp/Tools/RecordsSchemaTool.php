<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\McpRecordService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('records.schema')]
#[Description('Describe supported record types, fields, linked records, and tags.')]
#[IsReadOnly]
class RecordsSchemaTool extends Tool
{
    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->schema();
    }
}
