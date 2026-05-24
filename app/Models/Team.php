<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\TaskStatus;
use App\Enums\TeamFeature;
use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'is_personal', 'default_task_status_options', 'feature_settings'])]
class Team extends Model
{
    use GeneratesUniqueTeamSlugs;

    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team): void {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
        });

        static::updating(function (Team $team): void {
            if ($team->isDirty('name')) {
                $team->slug = static::generateUniqueTeamSlug($team->name, $team->id);
            }
        });
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<User, $this, Membership>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
            'default_task_status_options' => 'array',
            'feature_settings' => 'array',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function taskStatusDefaults(): array
    {
        $statusOptions = $this->getAttribute('default_task_status_options');

        if (is_array($statusOptions) && $statusOptions !== []) {
            return TaskStatus::normalizeOptions($statusOptions);
        }

        return TaskStatus::options();
    }

    /**
     * @return array<string, bool>
     */
    public function featureSettings(): array
    {
        $settings = $this->getAttribute('feature_settings');

        return array_replace(
            TeamFeature::defaults(),
            is_array($settings) ? array_intersect_key($settings, TeamFeature::defaults()) : [],
        );
    }

    public function hasFeature(TeamFeature|string $feature): bool
    {
        $value = $feature instanceof TeamFeature ? $feature->value : $feature;

        return $this->featureSettings()[$value] ?? true;
    }
}
