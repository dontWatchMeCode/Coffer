<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionCategory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionCategoryController extends Controller
{
    use EscapesLikeWildcards;

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

        $like = $this->likePattern($query);

        $categories = SubscriptionCategory::query()
            ->whereBelongsTo($currentTeam)
            ->where(function (Builder $query) use ($like): void {
                $this->whereLikeEscaped($query, 'name', $like);
                $this->whereLikeEscaped($query, 'slug', $like, 'or');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'categories' => $categories->map(fn (SubscriptionCategory $category): array => $category->toPayload())->values()->all(),
        ]);
    }
}
