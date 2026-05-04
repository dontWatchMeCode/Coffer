<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Tag;
use App\Models\Team;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasRecordTags
{
    public static function bootHasRecordTags(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'recordTags')) {
                $tagIds = $model->recordTags()->pluck('tags.id')->all();

                $model->recordTags()->detach();

                Tag::deleteUnused($tagIds);
            }
        });
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function recordTags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    public function formattedRecordTags(): array
    {
        $tags = $this->relationLoaded('recordTags')
            ? $this->getRelation('recordTags')
            : $this->recordTags()->orderBy('name')->get(['tags.id', 'tags.name', 'tags.slug']);

        return $tags
            ->map(fn (Tag $tag): array => [
                'id' => (int) $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $names
     */
    public function syncRecordTagNames(array $names, Team $team): void
    {
        $tagIds = collect($names)
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique(fn (string $name): string => Tag::slugFor($name))
            ->take(20)
            ->map(function (string $name) use ($team): int {
                $slug = Tag::slugFor($name);

                return (int) Tag::query()->firstOrCreate([
                    'team_id' => $team->id,
                    'slug' => $slug,
                ], [
                    'name' => $name,
                ])->id;
            })
            ->all();

        $changes = $this->recordTags()->sync($tagIds);

        $addedNames = Tag::query()->whereIn('id', $changes['attached'])->pluck('name')->all();
        $removedNames = Tag::query()->whereIn('id', $changes['detached'])->pluck('name')->all();

        ActivityLogger::logTagsSynced($this, $addedNames, $removedNames);

        Tag::deleteUnused($changes['detached']);
    }
}
