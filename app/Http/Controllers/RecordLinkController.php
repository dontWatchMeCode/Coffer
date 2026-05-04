<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LinkableRecord;
use App\Http\Requests\RecordLinks\RecordLinkCandidatesRequest;
use App\Http\Requests\RecordLinks\StoreRecordLinkRequest;
use App\Models\RecordLink;
use App\Models\Team;
use App\Services\ActivityLogger;
use App\Services\RecordSearchService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class RecordLinkController extends Controller
{
    public function __construct(private readonly RecordSearchService $recordSearch) {}

    /**
     * Resolve a model instance from its type and id within the current team.
     */
    protected function resolveModel(Team $currentTeam, string $type, int|string $id): ?Model
    {
        $map = RecordLink::linkableMap();
        $class = $map[$type] ?? null;

        if ($class === null) {
            return null;
        }

        return $class::query()->whereBelongsTo($currentTeam)->find((int) $id);
    }

    /**
     * Find a record link by its normalized pair.
     */
    protected function findLink(Team $currentTeam, string $leftType, int $leftId, string $rightType, int $rightId): ?RecordLink
    {
        return RecordLink::query()
            ->where('team_id', $currentTeam->id)
            ->where('left_type', $leftType)
            ->where('left_id', $leftId)
            ->where('right_type', $rightType)
            ->where('right_id', $rightId)
            ->first();
    }

    /**
     * Store a new record link.
     */
    public function store(StoreRecordLinkRequest $request, Team $currentTeam): JsonResponse
    {
        $validated = $request->validated();

        $from = $this->resolveModel($currentTeam, $validated['from_type'], $validated['from_id']);
        $to = $this->resolveModel($currentTeam, $validated['to_type'], $validated['to_id']);

        if (! $from instanceof Model || ! $to instanceof Model || ! $from instanceof LinkableRecord || ! $to instanceof LinkableRecord) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        if ($from->linkableType() === $to->linkableType() && $from->getKey() === $to->getKey()) {
            return response()->json(['message' => 'Cannot link a record to itself.'], 422);
        }

        [$leftType, $leftId, $rightType, $rightId] = $this->normalizePair(
            $from->linkableType(),
            $from->getKey(),
            $to->linkableType(),
            $to->getKey(),
        );

        if ($this->findLink($currentTeam, $leftType, $leftId, $rightType, $rightId) instanceof RecordLink) {
            return response()->json(['message' => 'Link already exists.'], 422);
        }

        try {
            $link = RecordLink::create([
                'team_id' => $currentTeam->id,
                'left_type' => $leftType,
                'left_id' => $leftId,
                'right_type' => $rightType,
                'right_id' => $rightId,
            ]);

            ActivityLogger::logLinkCreated($link);
        } catch (QueryException $queryException) {
            if ($queryException->getCode() === '23000') {
                return response()->json(['message' => 'Link already exists.'], 422);
            }

            throw $queryException;
        }

        return response()->json(['message' => 'Link created.'], 201);
    }

    /**
     * Remove a record link.
     */
    public function destroy(StoreRecordLinkRequest $request, Team $currentTeam): JsonResponse
    {
        $validated = $request->validated();

        $from = $this->resolveModel($currentTeam, $validated['from_type'], $validated['from_id']);
        $to = $this->resolveModel($currentTeam, $validated['to_type'], $validated['to_id']);

        if (! $from instanceof LinkableRecord || ! $to instanceof LinkableRecord) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        [$leftType, $leftId, $rightType, $rightId] = $this->normalizePair(
            $from->linkableType(),
            $from->getKey(),
            $to->linkableType(),
            $to->getKey(),
        );

        $link = $this->findLink($currentTeam, $leftType, $leftId, $rightType, $rightId);

        if (! $link instanceof RecordLink) {
            return response()->json(['message' => 'Link not found.'], 404);
        }

        ActivityLogger::logLinkDestroyed($link);
        $link->delete();

        return response()->json(['message' => 'Link removed.']);
    }

    /**
     * Search for linkable records excluding the current record and already linked records.
     */
    public function candidates(RecordLinkCandidatesRequest $request, Team $currentTeam): JsonResponse
    {
        $validated = $request->validated();

        $from = $this->resolveModel($currentTeam, $validated['from_type'], $validated['from_id']);

        if (! $from instanceof Model || ! $from instanceof LinkableRecord) {
            return response()->json(['records' => []]);
        }

        return response()->json([
            'records' => $this->recordSearch->linkableCandidates(
                $currentTeam,
                $from,
                $request->string('q')->trim()->toString(),
            ),
        ]);
    }

    /**
     * Normalize a pair so left <= right (by type string, then id).
     *
     * @return array{0: string, 1: int, 2: string, 3: int}
     */
    protected function normalizePair(string $typeA, int $idA, string $typeB, int $idB): array
    {
        if ($typeA < $typeB || ($typeA === $typeB && $idA < $idB)) {
            return [$typeA, $idA, $typeB, $idB];
        }

        return [$typeB, $idB, $typeA, $idA];
    }
}
