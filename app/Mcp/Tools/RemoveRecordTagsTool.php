<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\RegistersForWritableTokens;
use App\Services\McpRecordService;
use App\Services\RecordTypeRegistry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('records.tags.remove')]
#[Description('Remove one or more tags from a current-team record.')]
#[IsDestructive]
class RemoveRecordTagsTool extends Tool
{
    use RegistersForWritableTokens;

    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->removeTags($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return RecordToolSchemas::tags($schema, RecordTypeRegistry::mcpTaggableTypes());
    }
}
