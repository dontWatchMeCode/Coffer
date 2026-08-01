<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Records\CreateRecordLink;
use App\Actions\Records\DeleteRecordLink;
use App\Concerns\ResolvesLinkableRecord;
use App\Contracts\LinkableRecord;
use App\Http\Requests\RecordLinks\RecordLinkCandidatesRequest;
use App\Http\Requests\RecordLinks\StoreRecordLinkRequest;
use App\Models\RecordLink;
use App\Models\Team;
use App\Services\RecordSearchService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class RecordLinkController extends Controller
{
    use ResolvesLinkableRecord;

    public function __construct(
        private readonly RecordSearchService $recordSearch,
        private readonly CreateRecordLink $createRecordLink,
        private readonly DeleteRecordLink $deleteRecordLink,
    ) {}

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

        if ($this->createRecordLink->find($currentTeam, $from, $to) instanceof RecordLink) {
            return response()->json(['message' => 'Link already exists.'], 422);
        }

        try {
            $this->createRecordLink->execute($currentTeam, $from, $to);
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

        $link = $this->createRecordLink->find($currentTeam, $from, $to);

        if (! $link instanceof RecordLink) {
            return response()->json(['message' => 'Link not found.'], 404);
        }

        $this->deleteRecordLink->execute($link);

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
}
