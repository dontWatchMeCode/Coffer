<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use App\Enums\TaskStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'name', 'description', 'archived', 'status_options'])]
class Project extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;
    use Searchable;

    /**
     * @return array<string, mixed>
     */
    #[SearchUsingFullText(['name', 'description'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'team_id' => (int) $this->team_id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('projects')
            ->logOnly(['name', 'description'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Get the tasks for the project.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived' => 'boolean',
            'status_options' => 'array',
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function taskStatusOptions(): array
    {
        $statusOptions = $this->getAttribute('status_options');

        if (is_array($statusOptions) && $statusOptions !== []) {
            return TaskStatus::normalizeOptions($statusOptions);
        }

        return $this->team?->taskStatusDefaults() ?: TaskStatus::options();
    }

    /**
     * @return list<string>
     */
    public static function taskStatusValuesFor(Team $team, mixed $projectId): array
    {
        if ($projectId === null) {
            return TaskStatus::values();
        }

        $project = self::withoutGlobalScopes()
            ->whereBelongsTo($team)
            ->find($projectId);

        if (! $project instanceof self) {
            return TaskStatus::values();
        }

        return array_column($project->taskStatusOptions(), 'value');
    }
}
