<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Records\CreateRecordLink;
use App\Actions\Records\DeleteRecordLink;
use App\Contracts\LinkableRecord;
use App\Models\RecordLink;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

class McpRecordLinkService
{
    public function __construct(
        private readonly McpRequestContext $requestContext,
        private readonly McpTokenPermissionService $permissions,
        private readonly CreateRecordLink $createRecordLink,
        private readonly DeleteRecordLink $deleteRecordLink,
    ) {}

    public function link(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        [$from, $to] = $this->linkedPair($request, $team);

        if (! $from instanceof LinkableRecord || ! $to instanceof LinkableRecord) {
            return Response::error('Record not found.');
        }

        if (! $this->teamAllowsPair($team, $from, $to)) {
            return Response::error('Record not found.');
        }

        if (! $this->canLink($from, $to)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $from);
        Gate::forUser($user)->authorize('view', $to);
        Gate::forUser($user)->authorize('create', RecordLink::class);

        if ($from->linkableType() === $to->linkableType() && (int) $from->getKey() === (int) $to->getKey()) {
            return Response::error('Cannot link a record to itself.');
        }

        try {
            $this->createRecordLink->execute($team, $from, $to, $user);
        } catch (QueryException $queryException) {
            if ($queryException->getCode() === '23000') {
                return Response::error('Link already exists.');
            }

            throw $queryException;
        }

        return Response::structured([
            'link' => [
                'source' => McpRecordResolver::recordContext($from, $team),
                'target' => McpRecordResolver::recordContext($to, $team),
            ],
        ]);
    }

    public function unlink(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        [$from, $to] = $this->linkedPair($request, $team);

        if (! $from instanceof LinkableRecord || ! $to instanceof LinkableRecord) {
            return Response::error('Record not found.');
        }

        if (! $this->teamAllowsPair($team, $from, $to)) {
            return Response::error('Record not found.');
        }

        if (! $this->canLink($from, $to)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $from);
        Gate::forUser($user)->authorize('view', $to);
        $link = $this->createRecordLink->find($team, $from, $to);

        if (! $link instanceof RecordLink) {
            return Response::error('Link not found.');
        }

        Gate::forUser($user)->authorize('delete', $link);
        $this->deleteRecordLink->execute($link, $user);

        return Response::structured(['unlinked' => true]);
    }

    public function related(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! RecordTypeRegistry::teamAllowsType($team, $validated['type']) || ! $model instanceof Model || ! $model instanceof LinkableRecord) {
            return Response::error('Record not found.');
        }

        if (! $this->permissions->can($validated['type'], 'read', $model)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured([
            'record' => McpRecordResolver::recordContext($model, $team),
            'related' => $this->permissions->filterPayload(['related' => $model->formattedLinkedRecords($team)])['related'],
        ]);
    }

    private function canLink(LinkableRecord $from, LinkableRecord $to): bool
    {
        $fromModel = $from instanceof Model ? $from : null;
        $toModel = $to instanceof Model ? $to : null;

        return $this->permissions->can(McpRecordResolver::typeForClass($from::class), 'write', $fromModel)
            && $this->permissions->can(McpRecordResolver::typeForClass($to::class), 'write', $toModel);
    }

    private function teamAllowsPair(Team $team, LinkableRecord $from, LinkableRecord $to): bool
    {
        return RecordTypeRegistry::teamAllowsType($team, McpRecordResolver::typeForClass($from::class))
            && RecordTypeRegistry::teamAllowsType($team, McpRecordResolver::typeForClass($to::class));
    }

    /** @return array{0: Model|null, 1: Model|null} */
    private function linkedPair(Request $request, Team $team): array
    {
        $validated = $request->validate([
            'source_type' => ['required', 'string', Rule::in(RecordTypeRegistry::mcpTypes())],
            'source_id' => ['required', 'integer', 'min:1'],
            'target_type' => ['required', 'string', Rule::in(RecordTypeRegistry::mcpTypes())],
            'target_id' => ['required', 'integer', 'min:1'],
        ]);

        return [
            McpRecordResolver::resolveRecord($team, $validated['source_type'], (int) $validated['source_id']),
            McpRecordResolver::resolveRecord($team, $validated['target_type'], (int) $validated['target_id']),
        ];
    }
}
