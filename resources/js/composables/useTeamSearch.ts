import { computed, ref } from 'vue';
import type { Ref } from 'vue';
import { createEmptySearchResults, flattenSearchResults } from '@/lib/search';
import type { FlattenedSearchResult, SearchResponse } from '@/lib/search';
import teamRoutes from '@/routes/team';
import type { QueryParams } from '@/wayfinder';

function hasParams(params: QueryParams | undefined): params is QueryParams {
    return !!params && Object.keys(params).length > 0;
}

export function buildSearchJsonUrl(
    teamSlug: string | undefined,
    params?: QueryParams,
): string {
    if (!teamSlug) {
        return '';
    }

    return teamRoutes.search.url(
        { current_team: teamSlug },
        hasParams(params) ? { query: params } : undefined,
    );
}

export function buildSearchPageUrl(
    teamSlug: string | undefined,
    params?: QueryParams,
): string {
    if (!teamSlug) {
        return '';
    }

    return teamRoutes.search.page.url(
        { current_team: teamSlug },
        hasParams(params) ? { query: params } : undefined,
    );
}

export async function requestSearchResults(
    url: string,
): Promise<SearchResponse> {
    try {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return createEmptySearchResults();
        }

        return (await response.json()) as SearchResponse;
    } catch {
        return createEmptySearchResults();
    }
}

export function useTeamSearch(teamSlug: Ref<string | undefined>) {
    const loading = ref(false);
    const results = ref<SearchResponse>(createEmptySearchResults());
    const allResults = computed<FlattenedSearchResult[]>(() =>
        flattenSearchResults(results.value),
    );
    const hasResults = computed(() => allResults.value.length > 0);

    function searchJsonUrl(params?: QueryParams): string {
        return buildSearchJsonUrl(teamSlug.value, params);
    }

    function searchPageUrl(params?: QueryParams): string {
        return buildSearchPageUrl(teamSlug.value, params);
    }

    function resetResults(): void {
        results.value = createEmptySearchResults();
        loading.value = false;
    }

    async function runJsonSearch(params?: QueryParams): Promise<void> {
        results.value = await requestSearchResults(searchJsonUrl(params));
        loading.value = false;
    }

    return {
        loading,
        results,
        allResults,
        hasResults,
        resetResults,
        searchJsonUrl,
        searchPageUrl,
        runJsonSearch,
    };
}
