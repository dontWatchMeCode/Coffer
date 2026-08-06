<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\EscapesLikeWildcards;
use App\Concerns\HasRecordTags;
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
    public function global(Team $currentTeam, string $rawQuery, int $limit = 10): array
    {
        [$query, $scopes, $tagSlug] = $this->parseSearchPrefix($rawQuery, RecordTypeRegistry::globalPrefixMap());

        $results = $this->emptyResults();

        if ($query === '' && $tagSlug === null) {
            return $results;
        }

        $like = $query !== '' ? $this->likePattern($query) : null;

        foreach (RecordTypeRegistry::enabledDefinitions($currentTeam) as $definition) {
            $globalKey = $definition['global'];

            if (in_array($globalKey, $scopes, true)) {
                $results[$globalKey] = $this->globalResultsForDefinition($currentTeam, $definition, $like, $tagSlug, $limit);
            }
        }

        return $results;
    }

    /**
     * @return array<string, array<int, array{id: int, title: string, subtitle: string|null, url: string}>>
     */
    public function browse(Team $currentTeam, string $type, int $limit = 50): array
    {
        $results = $this->emptyResults();

        foreach (RecordTypeRegistry::enabledDefinitions($currentTeam) as $definition) {
            if ($definition['global'] === $type) {
                $results[$type] = $this->globalResultsForDefinition($currentTeam, $definition, null, null, $limit);

                break;
            }
        }

        return $results;
    }

    /**
     * @return array<int, array{id: int, type: string, title: string, url: string, preview: string|null}>
     */
    public function linkableCandidates(Team $currentTeam, LinkableRecord $from, string $rawQuery): array
    {
        [$query, $scopes, $tagSlug] = $this->parseSearchPrefix($rawQuery, RecordTypeRegistry::linkablePrefixMap());

        if ($query === '' && $tagSlug === null) {
            return [];
        }

        $linkedIds = $this->linkedIds($from, $currentTeam->id);
        $like = $query !== '' ? $this->likePattern($query) : null;
        $records = [];

        foreach (RecordTypeRegistry::enabledDefinitions($currentTeam) as $alias => $definition) {
            if (! in_array($alias, $scopes, true)) {
                continue;
            }

            $class = $definition['class'];
            $excludeIds = $linkedIds[$alias] ?? [];

            if ($class === $from->linkableType()) {
                $excludeIds[] = (int) $from->getKey();
            }

            $models = $this->baseSearchQuery($currentTeam, $definition, $like, $tagSlug)
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
    private function globalResultsForDefinition(Team $currentTeam, array $definition, ?string $like, ?string $tagSlug, int $limit = 10): array
    {
        return $this->baseSearchQuery($currentTeam, $definition, $like, $tagSlug)
            ->limit($limit)
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
    private function baseSearchQuery(Team $currentTeam, array $definition, ?string $like, ?string $tagSlug = null): Builder
    {
        $class = $definition['class'];
        $teamId = $currentTeam->id;

        $query = $class::query()
            ->whereBelongsTo($currentTeam)
            ->when($class === Note::class, fn (Builder $query) => $query->with('blocks'));

        if ($like !== null) {
            $query->where(function (Builder $query) use ($definition, $like): void {
                foreach ($definition['columns'] as $index => $column) {
                    $index === 0
                        ? $this->whereLikeEscaped($query, $column, $like)
                        : $this->whereLikeEscaped($query, $column, $like, 'or');
                }
            });
        }

        if ($tagSlug !== null) {
            $query->where(function (Builder $query) use ($class, $tagSlug, $teamId): void {
                if (in_array(HasRecordTags::class, class_uses_recursive($class))) {
                    $query->whereHas('recordTags', fn (Builder $q) => $q->where('tags.slug', $tagSlug)->where('tags.team_id', $teamId));
                } else {
                    $query->whereRaw('1 = 0');
                }
            });
        }

        return $query->orderBy($definition['order']);
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
            return $model->textExcerpt(90);
        }

        return RecordLinkHelper::previewForModel($model);
    }

    /**
     * @return array<string, array<int, array{id: int, title: string, subtitle: string|null, url: string}>>
     */
    public function emptyResults(): array
    {
        return collect(RecordTypeRegistry::definitions())
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
