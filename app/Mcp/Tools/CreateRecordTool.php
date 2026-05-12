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

#[Name('records.create')]
#[Description('Create a task, calendar event, contact, bookmark, note, collection, or log entry in the current team.')]
class CreateRecordTool extends Tool
{
    use RegistersForWritableTokens;

    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->create($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => RecordToolSchemas::type($schema)->required(),
            'data' => $schema->object()->description('Record attributes. Call records.schema for allowed fields and required fields.')->required(),
        ];
    }
}
