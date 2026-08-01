<?php

declare(strict_types=1);

namespace App\Actions\Records;

use App\Contracts\LinkableRecord;
use App\Models\RecordLink;
use App\Models\Team;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class CreateRecordLink
{
    public function execute(Team $team, LinkableRecord $from, LinkableRecord $to, ?Model $causer = null): RecordLink
    {
        [$leftType, $leftId, $rightType, $rightId] = self::normalizePair(
            $from->linkableType(),
            (int) $from->getKey(),
            $to->linkableType(),
            (int) $to->getKey(),
        );

        $link = RecordLink::create([
            'team_id' => $team->id,
            'left_type' => $leftType,
            'left_id' => $leftId,
            'right_type' => $rightType,
            'right_id' => $rightId,
        ]);

        ActivityLogger::logLinkCreated($link, $causer);

        return $link;
    }

    public function find(Team $team, LinkableRecord $from, LinkableRecord $to): ?RecordLink
    {
        [$leftType, $leftId, $rightType, $rightId] = self::normalizePair(
            $from->linkableType(),
            (int) $from->getKey(),
            $to->linkableType(),
            (int) $to->getKey(),
        );

        return RecordLink::query()
            ->where('team_id', $team->id)
            ->where('left_type', $leftType)
            ->where('left_id', $leftId)
            ->where('right_type', $rightType)
            ->where('right_id', $rightId)
            ->first();
    }

    /** @return array{0: string, 1: int, 2: string, 3: int} */
    public static function normalizePair(string $typeA, int $idA, string $typeB, int $idB): array
    {
        if ($typeA < $typeB || ($typeA === $typeB && $idA < $idB)) {
            return [$typeA, $idA, $typeB, $idB];
        }

        return [$typeB, $idB, $typeA, $idA];
    }
}
