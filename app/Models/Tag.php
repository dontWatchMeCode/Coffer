<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

#[Fillable(['team_id', 'name', 'slug'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use Searchable;

    /**
     * @return array<string, mixed>
     */
    #[SearchUsingFullText(['name', 'slug'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'team_id' => (int) $this->team_id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }

    public static function slugFor(string $name): string
    {
        return Str::slug(trim($name));
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public static function deleteUnused(array $ids): void
    {
        $ids = collect($ids)->map(fn (int|string $id): int => (int) $id)->unique()->values()->all();

        if ($ids === []) {
            return;
        }

        static::query()
            ->whereIn('id', $ids)
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('taggables')
                ->whereColumn('taggables.tag_id', 'tags.id'))
            ->delete();
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return MorphToMany<Bookmark, $this>
     */
    public function bookmarks(): MorphToMany
    {
        return $this->morphedByMany(Bookmark::class, 'taggable')->withTimestamps();
    }

    /**
     * @return MorphToMany<Note, $this>
     */
    public function notes(): MorphToMany
    {
        return $this->morphedByMany(Note::class, 'taggable')->withTimestamps();
    }
}
