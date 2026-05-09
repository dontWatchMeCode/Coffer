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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('records.unlink')]
#[Description('Remove a bidirectional related-record link between two current-team records.')]
#[IsDestructive]
class UnlinkRecordsTool extends Tool
{
    use RegistersForWritableTokens;

    public function handle(Request $request, McpRecordService $records): Response|ResponseFactory
    {
        return $records->unlink($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return RecordToolSchemas::pair($schema);
    }
}
