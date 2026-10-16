<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TeamMemberController extends Controller
{
    /**
     * Update the specified team member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Team $team, User $user): RedirectResponse
    {
        Gate::authorize('updateMember', $team);

        $newRole = TeamRole::from($request->validated('role'));

        $team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['role' => $newRole]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Team $team, User $user): RedirectResponse
    {
        Gate::authorize('removeMember', $team);

        $owner = $team->owner();
        abort_if($owner instanceof Model && $owner->is($user), 403, 'The team owner cannot be removed.');

        abort_if($team->memberships()->count() <= 1, 403, 'Cannot remove the last team member.');

        $team->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($user->isCurrentTeam($team)) {
            $personalTeam = $user->personalTeam();

            if ($personalTeam instanceof Team) {
                $user->switchTeam($personalTeam);
            }
        }

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
