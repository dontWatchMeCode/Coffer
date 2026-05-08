<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\McpToken;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

class McpTokenPermissionService
{
    public function currentToken(): ?McpToken
    {
        return app()->bound(McpToken::class) ? app(McpToken::class) : null;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function can(string $type, string $action, ?Model $model = null, ?array $data = null): bool
    {
        $token = $this->currentToken();

        if (! $token instanceof McpToken) {
            return true;
        }

        return $token->allows($type, $action, $this->projectId($type, $model, $data));
    }

    /**
     * @return list<string>
     */
    public function readableTypes(): array
    {
        $token = $this->currentToken();

        if (! $token instanceof McpToken) {
            return array_keys(McpToken::RECORD_TYPES);
        }

        /** @var list<string> $types */
        $types = collect(McpToken::RECORD_TYPES)
            ->keys()
            ->filter(fn (string $type): bool => $token->allows($type, 'read'))
            ->values()
            ->all();

        return $types;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public function filterPayload(array $record): array
    {
        $token = $this->currentToken();

        if (! $token instanceof McpToken || ! isset($record['related']) || ! is_array($record['related'])) {
            return $record;
        }

        $team = $token->team;

        if (! $team instanceof Team) {
            return $record;
        }

        $record['related'] = collect($record['related'])
            ->filter(function (array $related) use ($team): bool {
                $type = (string) ($related['type'] ?? '');
                $id = (int) ($related['id'] ?? 0);
                $model = $type !== '' && $id > 0
                    ? McpRecordResolver::resolveRecord($team, $type, $id)
                    : null;

                return $model instanceof Model && $this->can($type, 'read', $model);
            })
            ->values()
            ->all();

        return $record;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function projectId(string $type, ?Model $model, ?array $data): ?int
    {
        if ($type !== 'task') {
            return null;
        }

        if (isset($data['project_id'])) {
            return (int) $data['project_id'];
        }

        return $model instanceof Task ? (int) $model->project_id : null;
    }
}
