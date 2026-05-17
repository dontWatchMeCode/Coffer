<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityHistory;

use App\Concerns\ProvidesActivityHistory;
use App\Http\Controllers\Controller;
use App\Models\RecordLink;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityHistoryController extends Controller
{
    use ProvidesActivityHistory;

    public function index(Request $request, Team $currentTeam): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => ['required', 'string', Rule::in(array_keys(RecordLink::linkableMap()))],
            'subject_id' => ['required', 'integer', 'min:1'],
        ]);

        $class = RecordLink::linkableMap()[$validated['subject_type']] ?? null;

        if ($class === null || ! class_exists($class)) {
            return response()->json(['activities' => [], 'total' => 0, 'has_more' => false]);
        }

        $model = $class::query()->whereKey($validated['subject_id'])->first();

        if ($model === null) {
            return response()->json(['activities' => [], 'total' => 0, 'has_more' => false]);
        }

        if (! $model->getAttribute('team_id') || (int) $model->getAttribute('team_id') !== (int) $currentTeam->id) {
            return response()->json(['activities' => [], 'total' => 0, 'has_more' => false]);
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = 15;

        $result = $this->paginatedActivityHistoryPayload($model, $page, $perPage);

        return response()->json($result);
    }
}
