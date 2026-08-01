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
        $projectScope = $this->abilities['task_projects'] ?? null;

        if (! is_array($projectScope)) {
            return true;
        }

        $mode = $projectScope['mode'] ?? 'all';

        if ($mode === 'all') {
            return true;
        }

        if ($projectId === null) {
            return false;
        }

        $ids = $projectScope['ids'] ?? [];

        if (! is_array($ids)) {
            return false;
        }

        return in_array($projectId, array_map(intval(...), $ids), true);
    }

    /**
     * @return list<int>|null
     */
    public function taskProjectIds(): ?array
    {
        $projectScope = $this->abilities['task_projects'] ?? null;

        if (! is_array($projectScope)) {
            return null;
        }

        $mode = $projectScope['mode'] ?? 'all';

        if ($mode === 'all') {
            return null;
        }

        $ids = $projectScope['ids'] ?? [];

        if (! is_array($ids)) {
            return null;
        }

        return array_values(array_map(intval(...), $ids));
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
