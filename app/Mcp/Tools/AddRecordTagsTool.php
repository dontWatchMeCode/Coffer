<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\RegistersForWritableTokens;
use App\Services\McpRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('records.tags.add')]
#[Description('Add one or more tags to a current-team record. Missing tags are created.')]
class AddRecordTagsTool extends Tool
{
    use RegistersForWritableTokens;

    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->addTags($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return RecordToolSchemas::tags($schema);
    }
}
