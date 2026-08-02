<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\RegistersForWritableTokens;
use App\Services\McpTaskCommentService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('records.task_comments.add')]
#[Description('Add a comment to a current-team task. Requires write access to the task.')]
class AddTaskCommentTool extends Tool
{
    use RegistersForWritableTokens;

    public function handle(Request $request, McpTaskCommentService $records): Response|ResponseFactory
    {
        return $records->add($request);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->required(),
            'blocks' => $schema->array()->description('RTE blocks for the comment. Each block requires type, position, and optional payload.')->required(),
        ];
    }
}
