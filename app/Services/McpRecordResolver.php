<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;

class McpRecordResolver
{
    /**
     * @return class-string<Model>|null
     */
    public static function classFor(string $type): ?string
    {
        return RecordTypeRegistry::mcpResourceFor($type) !== null
            ? RecordTypeRegistry::classFor($type)
            : null;
    }

    public static function resolveRecord(Team $team, string $type, int $id): ?Model
    {
        $class = self::classFor($type);

        if ($class === null) {
            return null;
        }

        return $class::query()->whereBelongsTo($team)->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public static function validateTypeAndId(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'string', Rule::in(RecordTypeRegistry::mcpTypes())],
            'id' => ['required', 'integer', 'min:1'],
        ]);
    }

    public static function typeForClass(string $class): string
    {
        return RecordTypeRegistry::typeForClass($class) ?? 'unknown';
    }

    /**
     * @return array{id: int, type: string, title: string, url: string}
     */
    public static function recordContext(Model $model, Team $team): array
    {
        return [
            'id' => (int) $model->getKey(),
            'type' => self::typeForClass($model::class),
            'title' => RecordLinkHelper::titleForModel($model),
            'url' => RecordLinkHelper::urlForModel($model, $team),
        ];
    }
}
