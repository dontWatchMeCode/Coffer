<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Concerns\HasRecordLinks;
use App\Contracts\LinkableRecord;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Project;
use App\Models\RecordLink;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecordLinkController extends Controller
{
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
     * Validation rules for link store/destroy requests.
     *
     * @return array<string, mixed>
     */
    protected function linkValidationRules(): array
    {
        return [
            'from_type' => ['required', 'string', Rule::in(array_keys(RecordLink::linkableMap()))],
            'from_id' => ['required', 'integer', 'min:1'],
            'to_type' => ['required', 'string', Rule::in(array_keys(RecordLink::linkableMap()))],
            'to_id' => ['required', 'integer', 'min:1'],
        ];
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
    public function store(Request $request, Team $currentTeam): JsonResponse
    {
        $validated = Validator::make($request->all(), $this->linkValidationRules())->validate();

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
            RecordLink::create([
                'team_id' => $currentTeam->id,
                'left_type' => $leftType,
                'left_id' => $leftId,
                'right_type' => $rightType,
                'right_id' => $rightId,
            ]);
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
    public function destroy(Request $request, Team $currentTeam): JsonResponse
    {
        $validated = Validator::make($request->query(), $this->linkValidationRules())->validate();

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

        $link->delete();

        return response()->json(['message' => 'Link removed.']);
    }

    /**
     * Search for linkable records excluding the current record and already linked records.
     */
    public function candidates(Request $request, Team $currentTeam): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'q' => ['nullable', 'string', 'max:255'],
            'from_type' => ['required', 'string', Rule::in(array_keys(RecordLink::linkableMap()))],
            'from_id' => ['required', 'integer', 'min:1'],
        ])->validate();

        $from = $this->resolveModel($currentTeam, $validated['from_type'], $validated['from_id']);

        if (! $from instanceof Model || ! $from instanceof LinkableRecord) {
            return response()->json(['records' => []]);
        }

        $query = $request->string('q')->trim()->toString();
        $linkedIds = $this->linkedIds($from, $currentTeam->id);

        $records = [];

        foreach (RecordLink::linkableMap() as $type => $class) {
            if ($class === $from->linkableType()) {
                // Exclude self
                $excludeIds = array_merge($linkedIds[$type] ?? [], [$from->getKey()]);
            } else {
                $excludeIds = $linkedIds[$type] ?? [];
            }

            $q = $class::query()->whereBelongsTo($currentTeam);

            if ($excludeIds !== []) {
                $q->whereNotIn('id', $excludeIds);
            }

            if ($query !== '') {
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $query);
                $like = sprintf('%%%s%%', $escaped);
                $q->where(function ($builder) use ($like, $class): void {
                    if ($class === Bookmark::class) {
                        $builder->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('url', 'like', $like);
                    } elseif ($class === Contact::class) {
                        $builder->where('name', 'like', $like)
                            ->orWhere('address', 'like', $like)
                            ->orWhere('additional_info', 'like', $like);
                    } elseif ($class === Project::class) {
                        $builder->where('name', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    } elseif ($class === Task::class) {
                        $builder->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    } elseif ($class === CalendarEvent::class) {
                        $builder->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    } else {
                        $builder->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    }
                });
            }

            $models = $q->orderBy(
                match ($class) {
                    Contact::class => 'name',
                    Project::class => 'name',
                    default => 'title',
                }
            )->limit(20)->get();

            foreach ($models as $model) {
                if (count($records) >= 50) {
                    break 2;
                }

                $records[] = [
                    'id' => $model->getKey(),
                    'type' => $type,
                    'title' => HasRecordLinks::titleForModel($model),
                ];
            }
        }

        return response()->json(['records' => $records]);
    }

    /**
     * Get IDs already linked to the given model, grouped by type alias.
     *
     * @return array<string, list<int>>
     */
    protected function linkedIds(LinkableRecord $model, int $teamId): array
    {
        $grouped = RecordLink::linkedIdsGroupedByClass($model->linkableType(), $model->getKey(), $teamId);
        $map = array_flip(RecordLink::linkableMap());
        $result = [];

        foreach ($grouped as $modelClass => $ids) {
            $alias = $map[$modelClass] ?? null;
            if ($alias !== null) {
                $result[$alias] = $ids;
            }
        }

        return $result;
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
