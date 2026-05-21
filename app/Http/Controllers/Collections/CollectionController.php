<?php

declare(strict_types=1);

namespace App\Http\Controllers\Collections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collections\DeleteCollectionRequest;
use App\Http\Requests\Collections\SaveCollectionRequest;
use App\Models\RecordCollection;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class CollectionController extends Controller
{
    public function store(SaveCollectionRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', RecordCollection::class);

        $collection = RecordCollection::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

        return to_route('team.collections.show', [
            'current_team' => $currentTeam,
            'collection' => $collection->id,
        ]);
    }

    public function update(SaveCollectionRequest $request, Team $currentTeam, int $collection): RedirectResponse
    {
        $collection = RecordCollection::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($collection);

        $this->authorize('update', $collection);

        $collection->update($request->validated());

        return to_route('team.collections.show', [
            'current_team' => $currentTeam,
            'collection' => $collection->id,
        ]);
    }

    public function destroy(DeleteCollectionRequest $request, Team $currentTeam, int $collection): RedirectResponse
    {
        $collection = RecordCollection::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($collection);

        $this->authorize('delete', $collection);

        $collection->delete();

        return to_route('team.collections.index', [
            'current_team' => $currentTeam,
        ]);
    }

    public function restore(Team $currentTeam, int $collection): RedirectResponse
    {
        $collection = RecordCollection::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($collection);

        $this->authorize('restore', $collection);

        $collection->restore();

        return to_route('team.collections.trash', [
            'current_team' => $currentTeam,
        ]);
    }

    public function forceDestroy(Team $currentTeam, int $collection): RedirectResponse
    {
        $collection = RecordCollection::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($collection);

        $this->authorize('forceDelete', $collection);

        $collection->forceDelete();

        return to_route('team.collections.trash', [
            'current_team' => $currentTeam,
        ]);
    }
}
