<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\McpRecordService;
use App\Services\RecordTypeRegistry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('records.related')]
#[Description('List records linked to a current-team record.')]
#[IsReadOnly]
class GetRelatedRecordsTool extends Tool
{
    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->related($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return RecordToolSchemas::typeAndId($schema, RecordTypeRegistry::mcpLinkableTypes());
    }
}
