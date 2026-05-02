<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use LogicException;

trait BelongsToTeam
{
    /**
     * Bootstrap the trait.
     */
    public static function bootBelongsToTeam(): void
    {
        static::saving(function (Model $model): void {
            $currentTeamId = static::currentTeamId();
            $teamId = $model->getAttribute('team_id');

            if ($teamId === null && $currentTeamId !== null) {
                $model->setAttribute('team_id', $currentTeamId);
                $teamId = $currentTeamId;
            }

            if ($teamId === null) {
                throw new LogicException('A team-scoped record requires a team.');
            }

            if ($currentTeamId !== null && (int) $teamId !== $currentTeamId) {
                throw new LogicException('The record must belong to the current team.');
            }
        });

        static::addGlobalScope('current_team', function (Builder $query): void {
            $currentTeamId = static::currentTeamId();

            if ($currentTeamId !== null) {
                $query->where($query->qualifyColumn('team_id'), $currentTeamId);

                return;
            }

            throw new LogicException('A current team is required to query team-scoped records.');
        });
    }

    /**
     * Get the owning team.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Resolve the current team id from the active auth or execution context.
     */
    protected static function currentTeamId(): mixed
    {
        $user = Auth::user();

        if ($user !== null) {
            return $user->current_team_id;
        }

        return Context::get('current_team_id');
    }
}
