<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\RecordSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly RecordSearchService $recordSearch) {}

    /**
     * Search across team records.
     */
    public function __invoke(Request $request, Team $currentTeam): JsonResponse
    {
        return response()->json($this->recordSearch->global(
            $currentTeam,
            $request->string('q')->trim()->toString(),
        ));
    }
}
