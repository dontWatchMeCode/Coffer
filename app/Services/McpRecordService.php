<?php

declare(strict_types=1);

namespace App\Services;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

class McpRecordService
{
    public function __construct(
        private readonly McpRecordReadService $readService,
        private readonly McpRecordWriteService $writeService,
        private readonly McpRecordLinkService $linkService,
        private readonly McpRecordTagService $tagService,
        private readonly McpTaskCommentService $taskCommentService,
    ) {}

    public function schema(): ResponseFactory
    {
        return $this->readService->schema();
    }

    public function search(Request $request): Response|ResponseFactory
    {
        return $this->readService->search($request);
    }

    public function get(Request $request): Response|ResponseFactory
    {
        return $this->readService->get($request);
    }

    public function create(Request $request): Response|ResponseFactory
    {
        return $this->writeService->create($request);
    }

    public function update(Request $request): Response|ResponseFactory
    {
        return $this->writeService->update($request);
    }

    public function delete(Request $request): Response|ResponseFactory
    {
        return $this->writeService->delete($request);
    }

    public function link(Request $request): Response|ResponseFactory
    {
        return $this->linkService->link($request);
    }

    public function unlink(Request $request): Response|ResponseFactory
    {
        return $this->linkService->unlink($request);
    }

    public function related(Request $request): Response|ResponseFactory
    {
        return $this->linkService->related($request);
    }

    public function addTags(Request $request): Response|ResponseFactory
    {
        return $this->tagService->add($request);
    }

    public function removeTags(Request $request): Response|ResponseFactory
    {
        return $this->tagService->remove($request);
    }

    public function listTags(Request $request): Response|ResponseFactory
    {
        return $this->tagService->list($request);
    }

    public function listTaskComments(Request $request): Response|ResponseFactory
    {
        return $this->taskCommentService->list($request);
    }

    public function addTaskComment(Request $request): Response|ResponseFactory
    {
        return $this->taskCommentService->add($request);
    }
}
