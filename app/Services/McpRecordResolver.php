<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RecordLink;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;

class McpRecordResolver
{
    /** @var list<string> */
    public const RECORD_TYPES = ['task', 'calendar_event', 'contact', 'bookmark', 'subscription', 'note', 'collection', 'log_entry'];

    /**
     * @return class-string<Model>|null
     */
    public static function classFor(string $type): ?string
    {
        return in_array($type, self::RECORD_TYPES, true)
            ? (RecordSearchRegistry::definitions()[$type]['class'] ?? null)
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
            'type' => ['required', 'string', Rule::in(self::RECORD_TYPES)],
            'id' => ['required', 'integer', 'min:1'],
        ]);
    }

    /**
     * @return array{0: string, 1: int, 2: string, 3: int}
     */
    public static function normalizePair(string $typeA, int $idA, string $typeB, int $idB): array
    {
        if ($typeA < $typeB || ($typeA === $typeB && $idA < $idB)) {
            return [$typeA, $idA, $typeB, $idB];
        }

        return [$typeB, $idB, $typeA, $idA];
    }

    public static function findLink(Team $team, string $leftType, int $leftId, string $rightType, int $rightId): ?RecordLink
    {
        return RecordLink::query()
            ->where('team_id', $team->id)
            ->where('left_type', $leftType)
            ->where('left_id', $leftId)
            ->where('right_type', $rightType)
            ->where('right_id', $rightId)
            ->first();
    }

    public static function typeForClass(string $class): string
    {
        return array_flip(RecordLink::linkableMap())[$class] ?? 'unknown';
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
