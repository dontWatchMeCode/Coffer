<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Team;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\RedirectResponse;
use LogicException;

trait HandlesTrashedRecords
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  Closure(Model): array<string, mixed>  $extraRouteParams
     */
    protected function restoreTrashedRecord(Team $team, int $id, string $modelClass, string $trashRoute, ?Closure $extraRouteParams = null): RedirectResponse
    {
        $record = $modelClass::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull('deleted_at')
            ->whereBelongsTo($team)
            ->findOrFail($id);

        $this->authorize('restore', $record);

        if (! method_exists($record, 'restore')) {
            throw new LogicException('The record must use soft deletes.');
        }

        $routeParams = ['current_team' => $team, ...($extraRouteParams ? $extraRouteParams($record) : [])];
        $record->restore();

        return to_route($trashRoute, $routeParams);
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  Closure(Model): array<string, mixed>  $extraRouteParams
     */
    protected function forceDeleteTrashedRecord(Team $team, int $id, string $modelClass, string $trashRoute, ?Closure $extraRouteParams = null): RedirectResponse
    {
        $record = $modelClass::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull('deleted_at')
            ->whereBelongsTo($team)
            ->findOrFail($id);

        $this->authorize('forceDelete', $record);

        $routeParams = ['current_team' => $team, ...($extraRouteParams ? $extraRouteParams($record) : [])];
        $record->forceDelete();

        return to_route($trashRoute, $routeParams);
    }
}
