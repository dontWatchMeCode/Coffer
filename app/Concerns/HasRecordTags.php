<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasRecordTags
{
    public static function bootHasRecordTags(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'recordTags')) {
                if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                    return;
                }

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
}
