<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property TeamRole|null $role
 */
#[Fillable(['team_id', 'user_id', 'role'])]
#[Table(name: 'team_members')]
class Membership extends Pivot
{
    /**
     * Determine if a user belongs to the given team.
     */
    public static function userBelongsToTeam(int $userId, int $teamId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('team_id', $teamId)
            ->whereHas('team')
            ->exists();
    }

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    #[\Override]
    public $incrementing = true;

    /**
     * Get the team that the membership belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that belongs to this membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }
}
