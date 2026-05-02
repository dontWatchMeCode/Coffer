<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\EscapesLikeWildcards;
use App\Concerns\ParsesSearchPrefixes;
use App\Contracts\LinkableRecord;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Note;
use App\Models\RecordLink;
use App\Models\Team;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RecordSearchService
{
    use EscapesLikeWildcards;
    use ParsesSearchPrefixes;

    /**
     * @return array<string, array<int, array{id: int, title: string, subtitle: string|null, url: string}>>
     */
    public function global(Team $currentTeam, string $rawQuery): array
    {
        [$query, $scopes] = $this->parseSearchPrefix($rawQuery, RecordSearchRegistry::globalPrefixMap());

        $results = $this->emptyGlobalResults();

        if ($query === '') {
            return $results;
        }

        $like = $this->likePattern($query);

        foreach (RecordSearchRegistry::definitions() as $definition) {
            $globalKey = $definition['global'];

            if (in_array($globalKey, $scopes, true)) {
                $results[$globalKey] = $this->globalResultsForDefinition($currentTeam, $definition, $like);
            }
        }

        return $results;
    }

    /**
     * @return array<int, array{id: int, type: string, title: string, url: string, preview: string|null}>
     */
    public function linkableCandidates(Team $currentTeam, LinkableRecord $from, string $rawQuery): array
    {
        [$query, $scopes] = $this->parseSearchPrefix($rawQuery, RecordSearchRegistry::linkablePrefixMap());

        if ($query === '') {
            return [];
        }

        $linkedIds = $this->linkedIds($from, $currentTeam->id);
        $like = $this->likePattern($query);
        $records = [];

        foreach (RecordSearchRegistry::definitions() as $alias => $definition) {
            if (! in_array($alias, $scopes, true)) {
                continue;
            }

            $class = $definition['class'];
            $excludeIds = $linkedIds[$alias] ?? [];

            if ($class === $from->linkableType()) {
                $excludeIds[] = (int) $from->getKey();
            }

            $models = $this->baseSearchQuery($currentTeam, $definition, $like)
                ->when($excludeIds !== [], fn (Builder $query) => $query->whereNotIn('id', array_unique($excludeIds)))
                ->limit(20)
                ->get();

            foreach ($models as $model) {
                if (count($records) >= 50) {
                    break 2;
                }

                $records[] = [
                    'id' => (int) $model->getKey(),
                    'type' => $alias,
                    'title' => RecordLinkHelper::titleForModel($model),
                    'url' => RecordLinkHelper::urlForModel($model, $currentTeam),
                    'preview' => RecordLinkHelper::previewForModel($model),
                ];
            }
        }

        return $records;
    }

    /**
     * @param  array{class: class-string<Model>, columns: list<string>, order: string}  $definition
     * @return array<int, array{id: int, title: string, subtitle: string|null, url: string}>
     */
    private function globalResultsForDefinition(Team $currentTeam, array $definition, string $like): array
    {
        return $this->baseSearchQuery($currentTeam, $definition, $like)
            ->limit(10)
            ->get()
            ->map(fn (Model $model): array => [
                'id' => (int) $model->getKey(),
                'title' => RecordLinkHelper::titleForModel($model),
                'subtitle' => $this->subtitleForGlobalResult($model),
                'url' => RecordLinkHelper::urlForModel($model, $currentTeam),
            ])
            ->filter(fn (array $item): bool => $item['url'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array{class: class-string<Model>, columns: list<string>, order: string}  $definition
     * @return Builder<Model>
     */
    private function baseSearchQuery(Team $currentTeam, array $definition, string $like): Builder
    {
        $class = $definition['class'];

        return $class::query()
            ->whereBelongsTo($currentTeam)
            ->where(function (Builder $query) use ($definition, $like): void {
                foreach ($definition['columns'] as $index => $column) {
                    $index === 0
                        ? $query->where($column, 'like', $like)
                        : $query->orWhere($column, 'like', $like);
                }
            })
            ->orderBy($definition['order']);
    }

    private function subtitleForGlobalResult(Model $model): ?string
    {
        if ($model instanceof Contact) {
            $phones = collect($model->phone_numbers ?? [])->pluck('value')->filter()->implode(', ');
            $emails = collect($model->email_addresses ?? [])->pluck('value')->filter()->implode(', ');
            $parts = array_filter([$phones, $emails]);

            return $parts !== [] ? implode(' · ', $parts) : null;
        }

        if ($model instanceof CalendarEvent) {
            $date = $model->getAttribute('date');

            return $date instanceof DateTimeInterface ? $date->format('F j, Y') : null;
        }

        if ($model instanceof Note) {
            return str($model->getAttribute('body') ?? '')->stripTags()->squish()->limit(90)->toString() ?: null;
        }

        return RecordLinkHelper::previewForModel($model);
    }

    /**
     * @return array<string, array<int, array{id: int, title: string, subtitle: string|null, url: string}>>
     */
    private function emptyGlobalResults(): array
    {
        return collect(RecordSearchRegistry::definitions())
            ->mapWithKeys(fn (array $definition): array => [$definition['global'] => []])
            ->all();
    }

    /**
     * @return array<string, list<int>>
     */
    private function linkedIds(LinkableRecord $model, int $teamId): array
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
}
