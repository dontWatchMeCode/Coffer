<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\EscapesLikeWildcards;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

class McpRecordReadService
{
    use EscapesLikeWildcards;

    public function __construct(
        private readonly McpRequestContext $requestContext,
        private readonly McpTokenPermissionService $permissions,
    ) {}

    public function schema(): ResponseFactory
    {
        $types = RecordTypeRegistry::mcpTypes();

        return Response::structured([
            'types' => collect($types)->intersect($this->permissions->readableTypes())->mapWithKeys(fn (string $type): array => [
                $type => [
                    'fields' => McpRecordValidator::fieldsFor($type),
                    'create_required' => McpRecordValidator::requiredFieldsFor($type),
                    'searchable' => RecordTypeRegistry::searchableColumnsFor($type),
                    'field_notes' => McpRecordValidator::fieldNotesFor($type),
                ],
            ])->all(),
            'relationships' => [
                'supported_types' => $types,
                'relation_types' => ['related'],
                'note' => 'Links are bidirectional generic related-record links.',
            ],
            'tags' => [
                'max_per_record' => 20,
                'input' => 'Provide tag names. Slugs are generated automatically.',
            ],
            'task_comments' => [
                'tools' => ['records.task_comments.list', 'records.task_comments.add'],
                'note' => 'Task comments are listed and added separately from task record CRUD.',
            ],
        ]);
    }

    public function search(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [, $team] = $context;
        $mcpTypes = RecordTypeRegistry::mcpTypes();
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in($mcpTypes)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = trim((string) $validated['query']);
        $limit = (int) ($validated['limit'] ?? 20);
        $types = isset($validated['type']) ? [$validated['type']] : $mcpTypes;
        $readableTypes = array_values(array_filter(
            $this->permissions->readableTypes(),
            fn (string $type): bool => RecordTypeRegistry::teamAllowsType($team, $type),
        ));

        if (isset($validated['type']) && ! in_array($validated['type'], $readableTypes, true)) {
            return Response::error('Permission denied.');
        }

        $types = array_values(array_intersect($types, $readableTypes));

        if ($query === '' || $types === []) {
            return Response::structured(['records' => []]);
        }

        $like = $this->likePattern($query);
        $records = [];
        $perTypeLimit = count($types) > 1 ? (int) ceil($limit / count($types)) : $limit;

        foreach ($types as $type) {
            $definition = RecordTypeRegistry::definition($type);
            assert($definition !== null);
            $class = $definition['class'];

            $models = $class::query()
                ->whereBelongsTo($team)
                ->when(
                    $type === 'task' && $this->permissions->currentToken()?->taskProjectIds() !== null,
                    fn ($recordQuery) => $recordQuery->whereIn('project_id', $this->permissions->currentToken()?->taskProjectIds() ?? []),
                )
                ->where(function (Builder $recordQuery) use ($definition, $like): void {
                    foreach ($definition['columns'] as $index => $column) {
                        $index === 0
                            ? $this->whereLikeEscaped($recordQuery, $column, $like)
                            : $this->whereLikeEscaped($recordQuery, $column, $like, 'or');
                    }
                })
                ->orderBy($definition['order'])
                ->limit($perTypeLimit)
                ->get();

            foreach ($models as $model) {
                if (count($records) >= $limit) {
                    break 2;
                }

                $records[] = McpRecordPayload::forModel($model, $team);
            }
        }

        return Response::structured(['records' => $records]);
    }

    public function get(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = McpRecordResolver::validateTypeAndId($request);

        if (! RecordTypeRegistry::teamAllowsType($team, $validated['type'])) {
            return Response::error('Record not found.');
        }

        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model) {
            return Response::error('Record not found.');
        }

        if (! $this->permissions->can($validated['type'], 'read', $model)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured(['record' => $this->permissions->filterPayload(McpRecordPayload::forModel($model, $team, includeRelations: true))]);
    }
}
