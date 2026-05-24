<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Team;
use App\Services\RecordSearchRegistry;
use App\Services\RecordSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchPageController extends Controller
{
    public function __construct(private readonly RecordSearchService $recordSearch) {}

    public function __invoke(Request $request, Team $currentTeam): Response
    {
        $query = $request->string('q')->trim()->toString();
        $type = $request->string('type')->trim()->toString();
        $tagSlug = $request->string('tag')->trim()->toString();

        $hasText = $query !== '';
        $hasType = $type !== '';
        $hasTag = $tagSlug !== '';

        $results = match (true) {
            $hasText || $hasTag => $this->recordSearch->global($currentTeam, $this->buildQuery($query, $type, $tagSlug), limit: 50),
            $hasType => $this->recordSearch->browse($currentTeam, $type, limit: 50),
            default => [],
        };

        $tags = Tag::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->all();

        return Inertia::render('search/Index', [
            'query' => $query,
            'type' => $type,
            'tag' => $tagSlug,
            'results' => $results,
            'tags' => array_map(fn (Tag $tag): array => [
                'id' => (int) $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ], $tags),
            'types' => collect(RecordSearchRegistry::enabledDefinitions($currentTeam))
                ->map(fn (array $def, string $alias): array => [
                    'value' => $def['global'],
                    'label' => ucfirst(str_replace('_', ' ', $def['global'] === 'log_entries' ? 'log' : $def['global'])),
                    'prefix' => $def['prefix'],
                ])
                ->values()
                ->all(),
        ]);
    }

    private function buildQuery(string $query, string $type, string $tagSlug): string
    {
        $parts = [];

        $prefixMap = RecordSearchRegistry::globalPrefixMap();

        if ($type !== '') {
            $prefix = collect($prefixMap)->search(fn (string $global): bool => $global === $type);

            if ($prefix !== false) {
                $parts[] = $prefix.':';
            }
        }

        if ($tagSlug !== '') {
            $parts[] = '#'.$tagSlug;
        }

        if ($query !== '') {
            $parts[] = $query;
        }

        return implode(' ', $parts);
    }
}
