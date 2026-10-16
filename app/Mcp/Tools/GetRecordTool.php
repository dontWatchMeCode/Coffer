<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\McpRecordReadService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('records.get')]
#[Description('Get one record, including its tags and linked records.')]
#[IsReadOnly]
class GetRecordTool extends Tool
{
    public function handle(Request $request, McpRecordReadService $records): Response|ResponseFactory
    {
        return $records->get($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return RecordToolSchemas::typeAndId($schema);
    }
}
