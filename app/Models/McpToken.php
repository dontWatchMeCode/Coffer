<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\RecordTypeRegistry;
use Carbon\Carbon;
use Database\Factories\McpTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property array<string, mixed> $abilities
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 */
#[Fillable(['name', 'abilities', 'expires_at'])]
class McpToken extends Model
{
    /** @use HasFactory<McpTokenFactory> */
    use HasFactory;

    public const array PERMISSION_LEVELS = ['none', 'read', 'write'];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @param  array<string, mixed>  $abilities
     * @return array{0: self, 1: string}
     */
    public static function createToken(User $user, Team $team, string $name, array $abilities, ?string $expiresAt = null): array
    {
        $plainTextToken = 'mcp_'.Str::random(64);

        $token = new self;
        $token->forceFill([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'name' => $name,
            'token_hash' => self::hashToken($plainTextToken),
            'token' => $plainTextToken,
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ])->save();

        return [$token, $plainTextToken];
    }

    public function allows(string $type, string $action, ?int $projectId = null): bool
    {
        $resource = RecordTypeRegistry::mcpResourceFor($type);

        if ($resource === null) {
            return false;
        }

        $level = $this->abilities[$resource] ?? 'none';

        if (! in_array($level, ['read', 'write'], true)) {
            return false;
        }

        if ($action === 'write' && $level !== 'write') {
            return false;
        }

        if ($type !== 'task') {
            return true;
        }

        return $this->allowsTaskProject($projectId);
    }

    public function allowsTaskProject(?int $projectId): bool
    {
        $projectScope = $this->taskProjectScope();

        if ($projectScope['mode'] === 'all') {
            return true;
        }

        if ($projectId === null) {
            return false;
        }

        return in_array($projectId, $projectScope['ids'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedAbilities(): array
    {
        $abilities = [];

        foreach (RecordTypeRegistry::mcpResources() as $resource) {
            $level = $this->abilities[$resource] ?? 'none';
            $abilities[$resource] = in_array($level, self::PERMISSION_LEVELS, true) ? $level : 'none';
        }

        $abilities['task_projects'] = $this->taskProjectScope();

        return $abilities;
    }

    /**
     * Project ids a task-scoped token is limited to, or null when every project is allowed.
     *
     * @return list<int>|null
     */
    public function taskProjectIds(): ?array
    {
        $projectScope = $this->taskProjectScope();

        return $projectScope['mode'] === 'all' ? null : $projectScope['ids'];
    }

    /**
     * Stored task project scope, normalized so every reader agrees on malformed values.
     *
     * @return array{mode: 'all'|'only', ids: list<int>}
     */
    private function taskProjectScope(): array
    {
        $projectScope = $this->abilities['task_projects'] ?? null;
        $storedMode = is_array($projectScope) ? ($projectScope['mode'] ?? 'all') : 'all';

        if ($storedMode === 'all') {
            return ['mode' => 'all', 'ids' => []];
        }

        $ids = is_array($projectScope) && is_array($projectScope['ids'] ?? null)
            ? array_values(array_unique(array_map(intval(...), $projectScope['ids'])))
            : [];

        return ['mode' => 'only', 'ids' => $ids];
    }

    public function isExpired(): bool
    {
        $expires = $this->expires_at;

        return $expires instanceof Carbon && $expires->isPast();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'token' => 'encrypted',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
