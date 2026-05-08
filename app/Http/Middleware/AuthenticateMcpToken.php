<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\McpToken;
use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return response('Authentication required.', 401);
        }

        $token = McpToken::query()
            ->with(['team', 'user.teams'])
            ->where('token_hash', McpToken::hashToken($plainTextToken))
            ->first();

        if (! $token instanceof McpToken || $token->isExpired()) {
            return response('Invalid token.', 401);
        }

        $user = $token->user;
        $team = $token->team;

        if (! $user instanceof User || ! $team instanceof Team) {
            return response('Invalid token.', 401);
        }

        if (! $user->belongsToTeam($team)) {
            return response('Invalid token team.', 403);
        }

        $user->setAttribute('current_team_id', $team->id);
        $user->setRelation('currentTeam', $team);

        $previousUserResolver = Auth::userResolver();

        Auth::resolveUsersUsing(fn (): mixed => $user);
        app()->instance(McpToken::class, $token);

        try {
            $response = $next($request);

            $token->forceFill(['last_used_at' => now()])->save();

            return $response;
        } finally {
            Auth::resolveUsersUsing($previousUserResolver);
            app()->forgetInstance(McpToken::class);
        }
    }
}
