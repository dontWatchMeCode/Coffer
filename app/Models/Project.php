<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasActivityLog;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use App\Enums\TaskStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'name', 'description', 'archived', 'status_options'])]
class Project extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use HasActivityLog;

    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;

    protected static function activityLogName(): string
    {
        return 'projects';
    }

    /** @return list<string> */
    protected static function activityLogAttributes(): array
    {
        return ['name', 'description'];
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
