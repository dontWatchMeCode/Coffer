<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\EscapesLikeWildcards;
use App\Contracts\LinkableRecord;
use App\Models\RecordLink;
use App\Models\Tag;
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
        return Response::structured([
            'types' => collect(McpRecordResolver::RECORD_TYPES)->mapWithKeys(fn (string $type): array => [
                $type => [
                    'fields' => McpRecordValidator::fieldsFor($type),
                    'create_required' => McpRecordValidator::requiredFieldsFor($type),
                    'searchable' => RecordSearchRegistry::definitions()[$type]['columns'],
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
        ]);
    }

    public function search(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [, $team] = $context;

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = trim((string) $validated['query']);
        $limit = (int) ($validated['limit'] ?? 20);
        $types = isset($validated['type']) ? [$validated['type']] : McpRecordResolver::RECORD_TYPES;

        if ($query === '') {
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
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model) {
            return Response::error('Record not found.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured(['record' => McpRecordPayload::forModel($model, $team, includeRelations: true)]);
    }

    public function create(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
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
            McpRecordValidator::rulesFor($validated['type'], false, $team),
        );

        $data['team_id'] = $team->id;

        if ($validated['type'] === 'task') {
            $data['created_by'] ??= $user->id;
            $data['progress'] ??= 0;
            $data['position'] ??= 0;
        }

        /** @var Model $model */
        $model = $class::create($data);

        return Response::structured(['record' => McpRecordPayload::forModel($model->fresh(), $team)]);
    }

    public function update(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'id' => ['required', 'integer', 'min:1'],
            'data' => ['required', 'array'],
        ]);

        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model) {
            return Response::error('Record not found.');
        }

        Gate::forUser($user)->authorize('update', $model);

        $data = Validator::validate(
            $validated['data'],
            McpRecordValidator::rulesFor($validated['type'], true, $team),
        );

        $model->fill($data);
        $model->save();

        return Response::structured(['record' => McpRecordPayload::forModel($model->fresh(), $team)]);
    }

    public function delete(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model) {
            return Response::error('Record not found.');
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
        [$from, $to] = $this->linkedPair($request, $team);

        if (! $from instanceof LinkableRecord || ! $to instanceof LinkableRecord) {
            return Response::error('Record not found.');
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
        [$from, $to] = $this->linkedPair($request, $team);

        if (! $from instanceof LinkableRecord || ! $to instanceof LinkableRecord) {
            return Response::error('Record not found.');
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
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model || ! $model instanceof LinkableRecord) {
            return Response::error('Record not found.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured([
            'record' => McpRecordResolver::recordContext($model, $team),
            'related' => $model->formattedLinkedRecords($team),
        ]);
    }

    public function addTags(Request $request): Response|ResponseFactory
    {
        $context = $this->context($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        [$model, $tags] = $this->tagRequest($request, $team, $user);

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
        [$model, $tags] = $this->tagRequest($request, $team, $user);

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
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! $model instanceof Model || ! $model instanceof LinkableRecord || ! method_exists($model, 'recordTags')) {
            return Response::error('Record not found.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured(['tags' => $model->formattedRecordTags()]);
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
     * @return array{0: Model|null, 1: array<int, string>}
     */
    private function tagRequest(Request $request, Team $team, User $user): array
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(McpRecordResolver::RECORD_TYPES)],
            'id' => ['required', 'integer', 'min:1'],
            'tags' => ['required', 'array', 'min:1', 'max:20'],
            'tags.*' => ['required', 'string', 'max:50'],
        ]);

        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if ($model instanceof Model) {
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
