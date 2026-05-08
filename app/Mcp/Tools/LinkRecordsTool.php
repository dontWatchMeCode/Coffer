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

#[Name('records.link')]
#[Description('Create a bidirectional related-record link between two current-team records.')]
class LinkRecordsTool extends Tool
{
    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->link($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return RecordToolSchemas::pair($schema);
    }
}
