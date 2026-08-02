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

#[Name('records.search')]
#[Description('Search records in the current team. Optionally filter by record type.')]
#[IsReadOnly]
class SearchRecordsTool extends Tool
{
    public function handle(Request $request, McpRecordReadService $records): Response|ResponseFactory
    {
        return $records->search($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search text.')->required(),
            'type' => RecordToolSchemas::type($schema)->description('Optional record type filter.'),
            'limit' => $schema->integer()->description('Maximum records to return, 1-50.')->default(20),
        ];
    }
}
