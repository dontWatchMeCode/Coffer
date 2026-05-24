<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\EscapesLikeWildcards;
use App\Contracts\LinkableRecord;
use App\Models\Note;
use App\Models\RecordLink;
use App\Models\Subscription;
use App\Models\SubscriptionCategory;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

class McpRecordService
{
    use EscapesLikeWildcards;

    public function schema(): ResponseFactory
    {
        $permissions = app(McpTokenPermissionService::class);

        return Response::structured([
            'types' => collect(McpRecordResolver::RECORD_TYPES)->intersect($permissions->readableTypes())->mapWithKeys(fn (string $type): array => [
                $type => [
                    'fields' => McpRecordValidator::fieldsFor($type),
                    'create_required' => McpRecordValidator::requiredFieldsFor($type),
                    'searchable' => RecordSearchRegistry::definitions()[$type]['columns'],
                    'field_notes' => McpRecordValidator::fieldNotesFor($type),
                ],
            ])->all(),
            'relationships' => [
                'supported_types' => McpRecordResolver::RECORD_TYPES,
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
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = trim((string) $validated['query']);
        $limit = (int) ($validated['limit'] ?? 20);
        $types = isset($validated['type']) ? [$validated['type']] : McpRecordResolver::RECORD_TYPES;
        $readableTypes = $permissions->readableTypes();

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
            $definition = RecordSearchRegistry::definitions()[$type];
            $class = $definition['class'];

            $models = $class::query()
                ->whereBelongsTo($team)
                ->when(
                    $type === 'task' && $permissions->currentToken()?->taskProjectIds() !== null,
                    fn ($recordQuery) => $recordQuery->whereIn('project_id', $permissions->currentToken()?->taskProjectIds() ?? []),
                )
                ->where(function ($recordQuery) use ($definition, $like): void {
                    foreach ($definition['columns'] as $index => $column) {
                        $index === 0
                            ? $recordQuery->where($column, 'like', $like)
                            : $recordQuery->orWhere($column, 'like', $like);
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
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model) {
            return Response::error('Record not found.');
        }

        if (! $permissions->can($validated['type'], 'read', $model)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured(['record' => $permissions->filterPayload(McpRecordPayload::forModel($model, $team, includeRelations: true))]);
    }

    public function create(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'data' => ['required', 'array'],
        ]);

        $class = McpRecordResolver::classFor($validated['type']);

        if ($class === null) {
            return Response::error('Invalid record type.');
        }

        Gate::forUser($user)->authorize('create', $class);

        $data = Validator::validate(
            $validated['data'],
            McpRecordValidator::rulesFor($validated['type'], false, $team, $validated['data']),
            McpRecordValidator::messagesFor($validated['type']),
        );

        if (! $permissions->can($validated['type'], 'write', data: $data)) {
            return Response::error('Permission denied.');
        }

        $data['team_id'] = $team->id;

        if ($validated['type'] === 'note') {
            $blocks = $data['blocks'] ?? [];
            unset($data['blocks']);
        }

        if ($validated['type'] === 'task') {
            $data['created_by'] ??= $user->id;
            $data['progress'] ??= 0;
            $data['position'] ??= 0;
        }

        if ($validated['type'] === 'subscription' && isset($data['category'])) {
            $subscriptionCategoryId = SubscriptionCategory::resolveIdForTeam($data['category'], $team);
            unset($data['category']);
        }

        /** @var Model $model */
        $model = $class::create($data);

        if ($validated['type'] === 'note' && ! empty($blocks)) {
            assert($model instanceof Note);
            $model->syncBlocks($blocks);
        }

        if ($validated['type'] === 'subscription' && isset($subscriptionCategoryId)) {
            assert($model instanceof Subscription);
            $model->subscription_category_id = $subscriptionCategoryId;
            $model->save();
        }

        return Response::structured(['record' => McpRecordPayload::forModel($model->fresh(), $team)]);
    }

    public function update(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'id' => ['required', 'integer', 'min:1'],
            'data' => ['required', 'array'],
        ]);

        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model) {
            return Response::error('Record not found.');
        }

        $data = Validator::validate(
            $validated['data'],
            McpRecordValidator::rulesFor($validated['type'], true, $team, $validated['data'], $model),
            McpRecordValidator::messagesFor($validated['type']),
        );

        if (! $permissions->can($validated['type'], 'write', $model, $data)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('update', $model);

        $blocks = null;

        if ($validated['type'] === 'note') {
            $blocks = $data['blocks'] ?? null;
            unset($data['blocks']);
        }

        $model->fill($data);

        if ($validated['type'] === 'subscription' && array_key_exists('category', $data)) {
            assert($model instanceof Subscription);
            $oldCategoryId = $model->subscription_category_id;
            $model->subscription_category_id = SubscriptionCategory::resolveIdForTeam($data['category'], $team);
            unset($data['category']);
        }

        $model->save();

        if ($validated['type'] === 'note' && $blocks !== null) {
            assert($model instanceof Note);
            $model->syncBlocks($blocks);
        }

        if ($validated['type'] === 'subscription' && $model instanceof Subscription && isset($oldCategoryId) && $oldCategoryId !== $model->subscription_category_id && $oldCategoryId) {
            SubscriptionCategory::deleteUnused($team->id);
        }

        return Response::structured(['record' => McpRecordPayload::forModel($model->fresh(), $team)]);
    }

    public function delete(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model) {
            return Response::error('Record not found.');
        }

        if (! $permissions->can($validated['type'], 'write', $model)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('delete', $model);

        $model->delete();

        return Response::structured(['deleted' => ['type' => $validated['type'], 'id' => (int) $validated['id']]]);
    }

    public function link(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        [$from, $to] = $this->linkedPair($request, $team);

        if (! $from instanceof LinkableRecord || ! $to instanceof LinkableRecord) {
            return Response::error('Record not found.');
        }

        if (! $this->canLink($permissions, $from, $to)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $from);
        Gate::forUser($user)->authorize('view', $to);
        Gate::forUser($user)->authorize('create', RecordLink::class);

        if ($from->linkableType() === $to->linkableType() && (int) $from->getKey() === (int) $to->getKey()) {
            return Response::error('Cannot link a record to itself.');
        }

        [$leftType, $leftId, $rightType, $rightId] = McpRecordResolver::normalizePair(
            $from->linkableType(),
            (int) $from->getKey(),
            $to->linkableType(),
            (int) $to->getKey(),
        );

        try {
            $link = RecordLink::create([
                'team_id' => $team->id,
                'left_type' => $leftType,
                'left_id' => $leftId,
                'right_type' => $rightType,
                'right_id' => $rightId,
            ]);

            ActivityLogger::logLinkCreated($link, $user);
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
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        [$from, $to] = $this->linkedPair($request, $team);

        if (! $from instanceof LinkableRecord || ! $to instanceof LinkableRecord) {
            return Response::error('Record not found.');
        }

        if (! $this->canLink($permissions, $from, $to)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $from);
        Gate::forUser($user)->authorize('view', $to);

        [$leftType, $leftId, $rightType, $rightId] = McpRecordResolver::normalizePair(
            $from->linkableType(),
            (int) $from->getKey(),
            $to->linkableType(),
            (int) $to->getKey(),
        );

        $link = McpRecordResolver::findLink($team, $leftType, $leftId, $rightType, $rightId);

        if (! $link instanceof RecordLink) {
            return Response::error('Link not found.');
        }

        Gate::forUser($user)->authorize('delete', $link);
        ActivityLogger::logLinkDestroyed($link, $user);
        $link->delete();

        return Response::structured(['unlinked' => true]);
    }

    public function related(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model || ! $model instanceof LinkableRecord) {
            return Response::error('Record not found.');
        }

        if (! $permissions->can($validated['type'], 'read', $model)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured([
            'record' => McpRecordResolver::recordContext($model, $team),
            'related' => $permissions->filterPayload(['related' => $model->formattedLinkedRecords($team)])['related'],
        ]);
    }

    public function addTags(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $tagResult = $this->tagRequest($request, $team, $user);

        if ($tagResult instanceof Response) {
            return $tagResult;
        }

        [$model, $tags] = $tagResult;

        if (! $model instanceof Model || ! $model instanceof LinkableRecord || ! method_exists($model, 'recordTags')) {
            return Response::error('Record not found.');
        }

        $existingCount = $model->recordTags()->count();

        if ($existingCount + count($tags) > 20) {
            return Response::error('A record may only have 20 tags.');
        }

        foreach ($tags as $name) {
            $tag = $this->findOrCreateTag($team, $name);
            if (! $tag instanceof Tag) {
                continue;
            }

            if ($model->recordTags()->whereKey($tag->id)->exists()) {
                continue;
            }

            $model->recordTags()->attach($tag->id);
            ActivityLogger::logTagAttached($model, $tag, $user);
        }

        return Response::structured(['tags' => $model->formattedRecordTags()]);
    }

    public function removeTags(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $tagResult = $this->tagRequest($request, $team, $user);

        if ($tagResult instanceof Response) {
            return $tagResult;
        }

        [$model, $tags] = $tagResult;

        if (! $model instanceof Model || ! $model instanceof LinkableRecord || ! method_exists($model, 'recordTags')) {
            return Response::error('Record not found.');
        }

        $slugs = collect($tags)->map(fn (string $name): string => Tag::slugFor($name))->all();
        $tagModels = Tag::query()->whereBelongsTo($team)->whereIn('slug', $slugs)->get();

        foreach ($tagModels as $tag) {
            if (! $model->recordTags()->whereKey($tag->id)->exists()) {
                continue;
            }

            $model->recordTags()->detach($tag->id);
            ActivityLogger::logTagDetached($model, $tag, $user);
            Tag::deleteUnused([$tag->id]);
        }

        return Response::structured(['tags' => $model->formattedRecordTags()]);
    }

    public function listTags(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model || ! $model instanceof LinkableRecord || ! method_exists($model, 'recordTags')) {
            return Response::error('Record not found.');
        }

        if (! $permissions->can($validated['type'], 'read', $model)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured(['tags' => $model->formattedRecordTags()]);
    }

    public function listTaskComments(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $task = $this->resolveTask($team, (int) $validated['task_id']);

        if (! $task instanceof Task) {
            return Response::error('Task not found.');
        }

        if (! $permissions->can('task', 'read', $task)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $task);

        $comments = $task->comments()
            ->with(['blocks', 'user:id,name'])
            ->oldest()
            ->limit((int) ($validated['limit'] ?? 50))
            ->get()
            ->map(fn (TaskComment $comment): array => $this->taskCommentPayload($comment))
            ->all();

        return Response::structured([
            'task' => McpRecordResolver::recordContext($task, $team),
            'comments' => $comments,
        ]);
    }

    public function addTaskComment(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $permissions = app(McpTokenPermissionService::class);
        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'min:1'],
            'blocks' => ['required', 'array', 'min:1', 'max:50'],
            'blocks.*.type' => ['required', 'string', Rule::in(['text', 'excalidraw', 'mermaid'])],
            'blocks.*.position' => ['required', 'integer', 'min:0'],
            'blocks.*.payload' => ['sometimes', 'nullable', 'array'],
            'blocks.*.payload.content' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        $task = $this->resolveTask($team, (int) $validated['task_id']);

        if (! $task instanceof Task) {
            return Response::error('Task not found.');
        }

        if (! $permissions->can('task', 'write', $task)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('update', $task);
        Gate::forUser($user)->authorize('create', TaskComment::class);

        $comment = $task->comments()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'source' => 'mcp',
            'mcp_token_id' => $permissions->currentToken()?->id,
            'mcp_token_name' => $permissions->currentToken()?->name,
        ]);

        $comment->syncBlocks($validated['blocks']);

        return Response::structured(['comment' => $this->taskCommentPayload($comment->load(['blocks', 'user:id,name']))]);
    }

    /**
     * @return array{0: User, 1: Team}|Response
     */
    private function context(Request $request): array|Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication required.');
        }

        $user->loadTeamContext();
        $team = $user->currentTeam;

        if (! $team instanceof Team || ! $user->belongsToTeam($team)) {
            return Response::error('Current team required.');
        }

        return [$user, $team];
    }

    private function canLink(McpTokenPermissionService $permissions, LinkableRecord $from, LinkableRecord $to): bool
    {
        $fromModel = $from instanceof Model ? $from : null;
        $toModel = $to instanceof Model ? $to : null;

        return $permissions->can(McpRecordResolver::typeForClass($from::class), 'write', $fromModel)
            && $permissions->can(McpRecordResolver::typeForClass($to::class), 'write', $toModel);
    }

    private function resolveTask(Team $team, int $id): ?Task
    {
        return Task::query()->whereBelongsTo($team)->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function taskCommentPayload(TaskComment $comment): array
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

    /**
     * @return array<int, Model|null>
     */
    private function linkedPair(Request $request, Team $team): array
    {
        $validated = $request->validate([
            'source_type' => ['required', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'source_id' => ['required', 'integer', 'min:1'],
            'target_type' => ['required', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'target_id' => ['required', 'integer', 'min:1'],
        ]);

        $from = McpRecordResolver::resolveRecord($team, $validated['source_type'], (int) $validated['source_id']);
        $to = McpRecordResolver::resolveRecord($team, $validated['target_type'], (int) $validated['target_id']);

        return [$from, $to];
    }

    /**
     * @return array{0: Model|null, 1: array<int, string>}|Response
     */
    private function tagRequest(Request $request, Team $team, User $user): array|Response
    {
        $permissions = app(McpTokenPermissionService::class);
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'id' => ['required', 'integer', 'min:1'],
            'tags' => ['required', 'array', 'min:1', 'max:20'],
            'tags.*' => ['required', 'string', 'max:50'],
        ]);

        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if ($model instanceof Model) {
            if (! $permissions->can($validated['type'], 'write', $model)) {
                return Response::error('Permission denied.');
            }

            Gate::forUser($user)->authorize('update', $model);
        }

        $tags = [];
        $seenSlugs = [];

        foreach ($validated['tags'] as $tag) {
            $tag = trim((string) $tag);

            if ($tag === '') {
                continue;
            }

            $slug = Tag::slugFor($tag);

            if (! in_array($slug, $seenSlugs, true)) {
                $seenSlugs[] = $slug;
                $tags[] = $tag;
            }
        }

        return [$model, $tags];
    }

    private function findOrCreateTag(Team $team, string $name): ?Tag
    {
        $name = trim($name);
        $slug = Tag::slugFor($name);

        if ($name === '' || $slug === '') {
            return null;
        }

        return Tag::query()->firstOrCreate([
            'team_id' => $team->id,
            'slug' => $slug,
        ], [
            'name' => $name,
        ]);
    }
}
