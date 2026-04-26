<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRecordLinks;
use App\Contracts\LinkableRecord;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'name', 'description', 'archived'])]
class Project extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasRecordLinks;

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
        ];
    }
}
