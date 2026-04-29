<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'left_type', 'left_id', 'right_type', 'right_id'])]
class RecordLink extends Model
{
    /**
     * The allowed linkable model classes mapped by type alias.
     *
     * @return array<string, class-string<Model>>
     */
    public static function linkableMap(): array
    {
        return [
            'task' => Task::class,
            'project' => Project::class,
            'calendar_event' => CalendarEvent::class,
            'contact' => Contact::class,
            'bookmark' => Bookmark::class,
            'note' => Note::class,
            'collection' => RecordCollection::class,
        ];
    }

    /**
     * Query record links where the given model appears on either side.
     *
     * @return Builder<static>
     */
    public static function queryForModel(string $type, int|string $id, int $teamId): Builder
    {
        return static::query()
            ->where('team_id', $teamId)
            ->where(function ($query) use ($type, $id): void {
                $query->where(fn ($q) => $q->where('left_type', $type)->where('left_id', $id))
                    ->orWhere(fn ($q) => $q->where('right_type', $type)->where('right_id', $id));
            });
    }

    /**
     * Get linked record IDs grouped by model class.
     *
     * @return array<string, list<int>>
     */
    public static function linkedIdsGroupedByClass(string $type, int|string $id, int $teamId): array
    {
        $links = static::queryForModel($type, $id, $teamId)
            ->get(['left_type', 'left_id', 'right_type', 'right_id']);

        $grouped = [];

        $id = (int) $id;

        foreach ($links as $link) {
            if ($link->left_type === $type && (int) $link->left_id === $id) {
                $grouped[$link->right_type][] = (int) $link->right_id;
            } else {
                $grouped[$link->left_type][] = (int) $link->left_id;
            }
        }

        return $grouped;
    }

    /**
     * Get the team that owns the link.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
