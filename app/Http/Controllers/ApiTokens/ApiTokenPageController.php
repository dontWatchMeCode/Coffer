<?php

declare(strict_types=1);

namespace App\Http\Controllers\ApiTokens;

use App\Http\Controllers\Controller;
use App\Models\McpToken;
use App\Models\Project;
use App\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenPageController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        return Inertia::render('api-tokens/Index', [
            'tokens' => McpToken::query()
                ->with('user')
                ->whereBelongsTo($currentTeam)
                ->where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(function (McpToken $token): array {
                    $lastUsed = $token->last_used_at;
                    $expires = $token->expires_at;

                    return [
                        'id' => $token->id,
                        'name' => $token->name,
                        'token' => $token->token,
                        'abilities' => $token->abilities,
                        'last_used_at' => $lastUsed instanceof CarbonInterface ? $lastUsed->toISOString() : null,
                        'expires_at' => $expires instanceof CarbonInterface ? $expires->toDateString() : null,
                        'created_at' => $token->created_at instanceof CarbonInterface ? $token->created_at->toISOString() : null,
                        'created_by' => $token->user?->name,
                    ];
                }),
            'projects' => Project::query()
                ->whereBelongsTo($currentTeam)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                ]),
            'permissionLevels' => McpToken::PERMISSION_LEVELS,
            'mcpEndpointUrl' => route('mcp.records'),
        ]);
    }
}
