<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionCategory;
use App\Models\Team;
use App\Services\ScoutRecordSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionCategoryController extends Controller
{
    public function candidates(Request $request, Team $currentTeam): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();

        if ($query === '') {
            $categories = SubscriptionCategory::query()
                ->whereBelongsTo($currentTeam)
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'slug']);

            return response()->json([
                'categories' => $categories->map(fn (SubscriptionCategory $category): array => $category->toPayload())->values()->all(),
            ]);
        }

        $categoriesQuery = SubscriptionCategory::query()
            ->whereBelongsTo($currentTeam)
            ->tap(fn (Builder $categoryQuery): Builder => ScoutRecordSearch::constrain($categoryQuery, SubscriptionCategory::class, $currentTeam, $query));

        $categories = $categoriesQuery
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'categories' => $categories->map(fn (SubscriptionCategory $category): array => $category->toPayload())->values()->all(),
        ]);
    }
}
