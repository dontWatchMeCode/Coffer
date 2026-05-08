<?php

declare(strict_types=1);

namespace App\Http\Controllers\ApiTokens;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApiTokenRequest;
use App\Models\McpToken;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiTokenController extends Controller
{
    public function store(StoreApiTokenRequest $request, Team $currentTeam): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        McpToken::createToken(
            $user,
            $currentTeam,
            $request->validated('name'),
            $this->abilities($request->validated('abilities'), $currentTeam),
            $request->validated('expires_at'),
        );

        return back();
    }

    public function update(StoreApiTokenRequest $request, Team $currentTeam, McpToken $token): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->ensureTokenOwner($token, $user, $currentTeam);

        $token->update([
            'name' => $request->validated('name'),
            'abilities' => $this->abilities($request->validated('abilities'), $currentTeam),
            'expires_at' => $request->validated('expires_at'),
        ]);

        return back();
    }

    public function destroy(Request $request, Team $currentTeam, McpToken $token): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->ensureTokenOwner($token, $user, $currentTeam);

        $token->delete();

        return back();
    }

    private function ensureTokenOwner(McpToken $token, User $user, Team $currentTeam): void
    {
        abort_unless(
            (int) $token->team_id === (int) $currentTeam->id
            && (int) $token->user_id === (int) $user->id,
            404,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function abilities(array $input, Team $team): array
    {
        /** @var list<mixed> $rawIds */
        $rawIds = $input['task_projects']['ids'] ?? [];
        $ids = collect($rawIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (($input['task_projects']['mode'] ?? 'all') === 'only') {
            Validator::make([
                'ids' => $ids,
            ], [
                'ids' => ['array'],
                'ids.*' => [Rule::exists('projects', 'id')->where('team_id', $team->id)],
            ])->validate();
        }

        return [
            'collections' => $input['collections'],
            'notes' => $input['notes'],
            'bookmarks' => $input['bookmarks'],
            'contacts' => $input['contacts'],
            'calendar' => $input['calendar'],
            'tasks' => $input['tasks'],
            'task_projects' => [
                'mode' => $input['task_projects']['mode'],
                'ids' => $input['task_projects']['mode'] === 'only' ? $ids : [],
            ],
        ];
    }
}
