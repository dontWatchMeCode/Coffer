<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Concerns\EscapesLikeWildcards;
use App\Contracts\LinkableRecord;
use App\Http\Requests\RecordTags\DeleteRecordTagRequest;
use App\Http\Requests\RecordTags\RecordTagCandidatesRequest;
use App\Http\Requests\RecordTags\StoreRecordTagRequest;
use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class RecordTagController extends Controller
{
    use EscapesLikeWildcards;

    public function candidates(RecordTagCandidatesRequest $request, Team $currentTeam): JsonResponse
    {
        $validated = $request->validated();
        $from = $this->resolveModel($currentTeam, $validated['from_type'], $validated['from_id']);

        if (! $from instanceof Model || ! method_exists($from, 'recordTags')) {
            return response()->json(['tags' => []]);
        }

        $query = $request->string('q')->trim()->toString();

        if ($query === '') {
            return response()->json(['tags' => []]);
        }

        $attachedIds = $from->recordTags()->pluck('tags.id')->all();
        $like = $this->likePattern($query);

        $tags = Tag::query()
            ->whereBelongsTo($currentTeam)
            ->when($attachedIds !== [], fn ($tagQuery) => $tagQuery->whereNotIn('id', $attachedIds))
            ->where(fn ($tagQuery) => $tagQuery
                ->where('name', 'like', $like)
                ->orWhere('slug', 'like', $like))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Tag $tag): array => $this->tagPayload($tag))
            ->values()
            ->all();

        return response()->json(['tags' => $tags]);
    }

    public function store(StoreRecordTagRequest $request, Team $currentTeam): JsonResponse
    {
        $validated = $request->validated();
        $from = $this->resolveModel($currentTeam, $validated['from_type'], $validated['from_id']);

        if (! $from instanceof Model || ! method_exists($from, 'recordTags')) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        if ($from->recordTags()->count() >= 20) {
            return response()->json(['message' => 'A record may only have 20 tags.'], 422);
        }

        $tag = $this->resolveTag($currentTeam, $validated);

        if (! $tag instanceof Tag) {
            return response()->json(['message' => 'Tag not found.'], 404);
        }

        if ($from->recordTags()->whereKey($tag->id)->exists()) {
            return response()->json(['message' => 'Tag already exists on this record.'], 422);
        }

        $from->recordTags()->attach($tag->id);

        return response()->json([
            'message' => 'Tag added.',
            'tag' => $this->tagPayload($tag),
        ], 201);
    }

    public function destroy(DeleteRecordTagRequest $request, Team $currentTeam): JsonResponse
    {
        $validated = $request->validated();
        $from = $this->resolveModel($currentTeam, $validated['from_type'], $validated['from_id']);

        if (! $from instanceof Model || ! method_exists($from, 'recordTags')) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        $tag = Tag::query()
            ->whereBelongsTo($currentTeam)
            ->find($validated['tag_id'] ?? null);

        if (! $tag instanceof Tag || ! $from->recordTags()->whereKey($tag->id)->exists()) {
            return response()->json(['message' => 'Tag not found.'], 404);
        }

        $from->recordTags()->detach($tag->id);

        return response()->json(['message' => 'Tag removed.']);
    }

    protected function resolveModel(Team $currentTeam, string $type, int|string $id): ?Model
    {
        $class = RecordLink::linkableMap()[$type] ?? null;

        if ($class === null) {
            return null;
        }

        $model = $class::query()->whereBelongsTo($currentTeam)->find((int) $id);

        return $model instanceof LinkableRecord ? $model : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function resolveTag(Team $currentTeam, array $validated): ?Tag
    {
        if (filled($validated['tag_id'] ?? null)) {
            return Tag::query()->whereBelongsTo($currentTeam)->find((int) $validated['tag_id']);
        }

        $name = trim((string) ($validated['name'] ?? ''));
        $slug = Tag::slugFor($name);

        if ($name === '' || $slug === '') {
            return null;
        }

        return Tag::query()->firstOrCreate([
            'team_id' => $currentTeam->id,
            'slug' => $slug,
        ], [
            'name' => $name,
        ]);
    }

    /**
     * @return array{id: int, name: string, slug: string}
     */
    protected function tagPayload(Tag $tag): array
    {
        return [
            'id' => (int) $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
        ];
    }
}
