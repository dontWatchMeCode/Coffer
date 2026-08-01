<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

class McpTaskCommentService
{
    public function __construct(
        private readonly McpRequestContext $requestContext,
        private readonly McpTokenPermissionService $permissions,
    ) {}

    public function list(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $task = $this->resolveTask($team, (int) $validated['task_id']);

        if (! RecordTypeRegistry::teamAllowsType($team, 'task') || ! $task instanceof Task) {
            return Response::error('Task not found.');
        }

        if (! $this->permissions->can('task', 'read', $task)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $task);
        $comments = $task->comments()
            ->with(['blocks', 'user:id,name'])
            ->oldest()
            ->limit((int) ($validated['limit'] ?? 50))
            ->get()
            ->map(fn (TaskComment $comment): array => $this->payload($comment))
            ->all();

        return Response::structured([
            'task' => McpRecordResolver::recordContext($task, $team),
            'comments' => $comments,
        ]);
    }

    public function add(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'min:1'],
            'blocks' => ['required', 'array', 'min:1', 'max:50'],
            'blocks.*.type' => ['required', 'string', Rule::in(['text', 'excalidraw', 'mermaid'])],
            'blocks.*.position' => ['required', 'integer', 'min:0'],
            'blocks.*.payload' => ['sometimes', 'nullable', 'array'],
            'blocks.*.payload.content' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);
        $task = $this->resolveTask($team, (int) $validated['task_id']);

        if (! RecordTypeRegistry::teamAllowsType($team, 'task') || ! $task instanceof Task) {
            return Response::error('Task not found.');
        }

        if (! $this->permissions->can('task', 'write', $task)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('update', $task);
        Gate::forUser($user)->authorize('create', TaskComment::class);
        $comment = $task->comments()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'source' => 'mcp',
            'mcp_token_id' => $this->permissions->currentToken()?->id,
            'mcp_token_name' => $this->permissions->currentToken()?->name,
        ]);
        $comment->syncBlocks($validated['blocks']);

        return Response::structured(['comment' => $this->payload($comment->load(['blocks', 'user:id,name']))]);
    }

    private function resolveTask(Team $team, int $id): ?Task
    {
        return Task::query()->whereBelongsTo($team)->find($id);
    }

    /** @return array<string, mixed> */
    private function payload(TaskComment $comment): array
    {
        return [
            'id' => (int) $comment->id,
            'task_id' => (int) $comment->task_id,
            'blocks' => $comment->blocks->map(fn ($block): array => $block->toPayloadArray())->all(),
            'author' => [
                'id' => (int) $comment->user_id,
                'name' => $comment->user?->name,
            ],
            'source' => $comment->source ?? 'user',
            'mcp_token' => $comment->mcp_token_id === null ? null : [
                'id' => (int) $comment->mcp_token_id,
                'name' => $comment->mcp_token_name,
            ],
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
        ];
    }
}
