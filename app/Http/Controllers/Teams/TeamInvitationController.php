<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AcceptTeamInvitationRequest;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Rules\ValidTeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TeamInvitationController extends Controller
{
    /**
     * Show the invitation acceptance page.
     */
    public function show(Request $request, TeamInvitation $invitation): SymfonyResponse
    {
        $user = $request->user();

        $validator = Validator::make(
            ['invitation' => $invitation],
            ['invitation' => ['required', new ValidTeamInvitation($user)]],
        );

        if ($validator->fails()) {
            return redirect()->route('dashboard')->withErrors($validator);
        }

        $team = $invitation->team;
        $inviter = $invitation->inviter;

        if ($team === null || $inviter === null) {
            abort(500);
        }

        return Inertia::render('invitations/Accept', [
            'invitation' => [
                'code' => $invitation->code,
                'teamName' => $team->name,
                'inviterName' => $inviter->name,
                'role' => $invitation->role->value,
                'roleLabel' => $invitation->role->label(),
            ],
        ])->toResponse($request);
    }

    /**
     * Store a newly created invitation.
     */
    public function store(CreateTeamInvitationRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('inviteMember', $team);

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $invitation = $team->invitations()->create([
            'email' => $request->validated('email'),
            'role' => TeamRole::from($request->validated('role')),
            'invited_by' => $user->id,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(Team $team, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->team_id === $team->id, 404);

        Gate::authorize('cancelInvitation', $team);

        $invitation->delete();

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(AcceptTeamInvitationRequest $request, TeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        DB::transaction(function () use ($user, $invitation): void {
            $team = $invitation->team;

            if ($team === null) {
                abort(500);
            }

            $team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);

            $user->switchTeam($team);
        });

        return to_route('dashboard');
    }
}
