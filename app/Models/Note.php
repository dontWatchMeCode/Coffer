<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Concerns\HasRteBlocks;
use App\Contracts\LinkableRecord;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'title'])]
class Note extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use Filterable;

    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use HasRteBlocks;
    use LogsActivity;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (self $note): void {
            if (! $note->isForceDeleting()) {
                return;
            }

            $note->blocks()->delete();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('notes')
            ->logOnly(['title'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function textExcerpt(int $limit = 180): ?string
    {
        $firstTextBlock = $this->relationLoaded('blocks')
            ? $this->blocks->first(fn ($b): bool => $b->type === 'text' && ! empty($b->payload['content']))
            : $this->blocks()->where('type', 'text')->whereNotNull('payload')->first();

        if ($firstTextBlock && ! empty($firstTextBlock->payload['content'])) {
            return Str::of((string) ($firstTextBlock->payload['content'] ?? ''))->stripTags()->squish()->limit($limit)->toString() ?: null;
        }

        return null;
    }

    public function hasDrawingBlock(): bool
    {
        if ($this->relationLoaded('blocks')) {
            return $this->blocks->contains(fn ($b): bool => $b->type === 'excalidraw');
        }

        return $this->blocks()->where('type', 'excalidraw')->exists();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function firstDrawingPayload(): ?array
    {
        $block = $this->relationLoaded('blocks')
            ? $this->blocks->first(fn ($b): bool => $b->type === 'excalidraw')
            : $this->blocks()->where('type', 'excalidraw')->first();

        $payload = $block?->payload;

        return is_array($payload) ? ($payload['scene'] ?? null) : null;
    }
}
