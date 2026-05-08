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

#[Name('records.update')]
#[Description('Update one record in the current team.')]
class UpdateRecordTool extends Tool
{
    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->update($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            ...RecordToolSchemas::typeAndId($schema),
            'data' => $schema->object()->description('Partial record attributes to update.')->required(),
        ];
    }
}
