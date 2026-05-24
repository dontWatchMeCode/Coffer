<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TeamFeature;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamFeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $team = $request->route('current_team');
        $teamFeature = TeamFeature::tryFrom($feature);

        abort_if(! $team instanceof Team || ! $teamFeature instanceof TeamFeature, 404);
        abort_unless($team->hasFeature($teamFeature), 404);

        return $next($request);
    }
}
