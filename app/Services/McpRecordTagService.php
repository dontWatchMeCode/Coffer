<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Records\AttachRecordTag;
use App\Actions\Records\DetachRecordTag;
use App\Actions\Records\FindOrCreateTag;
use App\Contracts\LinkableRecord;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

class McpRecordTagService
{
    public function __construct(
        private readonly McpRequestContext $requestContext,
        private readonly McpTokenPermissionService $permissions,
        private readonly FindOrCreateTag $findOrCreateTag,
        private readonly AttachRecordTag $attachRecordTag,
        private readonly DetachRecordTag $detachRecordTag,
    ) {}

    public function add(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

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

        if (! RecordTypeRegistry::teamAllowsType($team, McpRecordResolver::typeForClass($model::class))) {
            return Response::error('Record not found.');
        }

        if ($model->recordTags()->count() + count($tags) > 20) {
            return Response::error('A record may only have 20 tags.');
        }

        foreach ($tags as $name) {
            $tag = $this->findOrCreateTag->execute($team, $name);
            if (! $tag instanceof Tag) {
                continue;
            }

            if ($model->recordTags()->whereKey($tag->id)->exists()) {
                continue;
            }

            $this->attachRecordTag->execute($model, $model->recordTags(), $tag, $user);
        }

        return Response::structured(['tags' => $model->formattedRecordTags()]);
    }

    public function remove(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

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

        if (! RecordTypeRegistry::teamAllowsType($team, McpRecordResolver::typeForClass($model::class))) {
            return Response::error('Record not found.');
        }

        $slugs = collect($tags)->map(fn (string $name): string => Tag::slugFor($name))->all();
        $tagModels = Tag::query()->whereBelongsTo($team)->whereIn('slug', $slugs)->get();

        foreach ($tagModels as $tag) {
            if (! $model->recordTags()->whereKey($tag->id)->exists()) {
                continue;
            }

            $this->detachRecordTag->execute($model, $model->recordTags(), $tag, $user);
        }

        return Response::structured(['tags' => $model->formattedRecordTags()]);
    }

    public function list(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = McpRecordResolver::validateTypeAndId($request, RecordTypeRegistry::mcpTaggableTypes());
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! RecordTypeRegistry::teamAllowsType($team, $validated['type']) || ! $model instanceof Model || ! $model instanceof LinkableRecord || ! method_exists($model, 'recordTags')) {
            return Response::error('Record not found.');
        }

        if (! $this->permissions->can($validated['type'], 'read', $model)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('view', $model);

        return Response::structured(['tags' => $model->formattedRecordTags()]);
    }

    /** @return array{0: Model|null, 1: list<string>}|Response */
    private function tagRequest(Request $request, Team $team, User $user): array|Response
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(RecordTypeRegistry::mcpTaggableTypes())],
            'id' => ['required', 'integer', 'min:1'],
            'tags' => ['required', 'array', 'min:1', 'max:20'],
            'tags.*' => ['required', 'string', 'max:50'],
        ]);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if ($model instanceof Model) {
            if (! $this->permissions->can($validated['type'], 'write', $model)) {
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
}
