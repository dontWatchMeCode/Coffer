<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\McpRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('records.tags.list')]
#[Description('List tags attached to a current-team record.')]
#[IsReadOnly]
class ListRecordTagsTool extends Tool
{
    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->listTags($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return RecordToolSchemas::typeAndId($schema);
    }
}
