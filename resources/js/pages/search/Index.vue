<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import PageHeader from '@/components/page/PageHeader.vue';
import SearchFilterSidebar from '@/components/pages/search/SearchFilterSidebar.vue';
import SearchPrefixTooltip from '@/components/search/SearchPrefixTooltip.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import {
    SEARCH_CATEGORIES,
    buildSearchJsonUrl,
    buildSearchPageUrl,
    requestSearchResults,
} from '@/composables/useTeamSearch';
import { createEmptySearchResults, flattenSearchResults } from '@/lib/search';
import type { SearchResponse } from '@/lib/search';
import type { Team } from '@/types';

type TagItem = { id: number; name: string; slug: string };
type TypeOption = { value: string; label: string; prefix: string };

type Props = {
    query: string;
    type: string;
    tag: string;
    results: SearchResponse;
    tags: TagItem[];
    types: TypeOption[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(
    () => page.props.currentTeam?.slug as string | undefined,
);

const searchQuery = ref(props.query);
const activeType = ref(props.type);
const activeTag = ref(props.tag);
const loading = ref(false);
const localResults = ref<SearchResponse>(props.results);
const inputRef = ref<{ focus: () => void } | null>(null);

const allResults = computed(() => flattenSearchResults(localResults.value));
const hasResults = computed(() => allResults.value.length > 0);

const activeTagName = computed(() => {
    if (!activeTag.value) {
        return '';
    }

    return (
        props.tags.find((t) => t.slug === activeTag.value)?.name ??
        activeTag.value
    );
});

function iconForType(key: string): Component {
    return SEARCH_CATEGORIES.find((c) => c.key === key)?.icon ?? Search;
}

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(searchQuery, () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    loading.value = true;
    debounceTimer = setTimeout(async () => {
        await performSearch();
    }, 200);
});

watch(
    () => props.results,
    (newResults) => {
        localResults.value = newResults;
        loading.value = false;
    },
);

async function performSearch(): Promise<void> {
    const q = searchQuery.value.trim();

    if (!currentTeamSlug.value) {
        return;
    }

    if (!q && !activeType.value && !activeTag.value) {
        localResults.value = createEmptySearchResults();
        loading.value = false;
        updateUrl();

        return;
    }

    if (!q && !activeTag.value) {
        updateUrl();

        router.visit(
            buildSearchPageUrl(currentTeamSlug.value, {
                type: activeType.value,
            }),
            { preserveState: true, preserveScroll: true },
        );

        return;
    }

    try {
        const builtQuery = buildPrefixedQuery(
            q,
            activeType.value,
            activeTag.value,
        );

        localResults.value = await requestSearchResults(
            buildSearchJsonUrl(
                currentTeamSlug.value,
                builtQuery ? { q: builtQuery } : {},
            ),
        );
    } finally {
        loading.value = false;
    }

    updateUrl();
}

function buildPrefixedQuery(q: string, type: string, tagSlug: string): string {
    const parts: string[] = [];

    if (type) {
        const typeOption = props.types.find((t) => t.value === type);

        if (typeOption) {
            parts.push(`${typeOption.prefix}:`);
        }
    }

    if (tagSlug) {
        parts.push(`#${tagSlug}`);
    }

    if (q) {
        parts.push(q);
    }

    return parts.join(' ');
}

function updateUrl(): void {
    const url = new URL(window.location.href);
    const q = searchQuery.value.trim();
    url.searchParams.set('q', q || '');
    url.searchParams.set('type', activeType.value || '');
    url.searchParams.set('tag', activeTag.value || '');
    window.history.replaceState(window.history.state, '', url.toString());
}

function onSidebarTypeChange(value: string): void {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }

    activeType.value = value;
    loading.value = true;
    performSearch();
}

function onSidebarTagChange(value: string): void {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }

    activeTag.value = value;
    loading.value = true;
    performSearch();
}

function clearFilters(): void {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }

    activeType.value = '';
    activeTag.value = '';
    loading.value = true;
    performSearch();
}

defineOptions({
    inheritAttrs: false,
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Search',
                href: layoutProps.currentTeam
                    ? `/${layoutProps.currentTeam.slug}/search/page`
                    : '/search/page',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Search" />

    <PageHeader
        title="Search"
        description="Search across all your team records."
    />

    <div class="min-w-0 flex-1 px-4 py-6">
        <div class="mx-auto w-full max-w-7xl">
            <div class="mb-4 flex items-center gap-2">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        ref="inputRef"
                        v-model="searchQuery"
                        data-testid="search-page-input"
                        placeholder="Search tasks, contacts, events... use #tag to filter by tag"
                        class="h-11 pl-10"
                        autofocus
                    />
                </div>
                <SearchPrefixTooltip side="bottom" />
            </div>

            <div class="mx-auto flex flex-col gap-8 xl:flex-row">
                <div class="order-2 min-w-0 flex-1 space-y-6 xl:order-1">
                    <div
                        v-if="activeType || activeTag"
                        class="flex flex-wrap items-center gap-1.5"
                    >
                        <span class="text-xs text-muted-foreground"
                            >Filtered by:</span
                        >
                        <Badge
                            v-if="activeType"
                            variant="secondary"
                            class="gap-1 text-xs"
                        >
                            <component
                                :is="iconForType(activeType)"
                                class="h-3 w-3"
                            />
                            {{
                                types.find((t) => t.value === activeType)
                                    ?.label ?? activeType
                            }}
                            <button
                                class="ml-0.5 rounded-full hover:text-destructive"
                                @click="onSidebarTypeChange('')"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </Badge>
                        <Badge
                            v-if="activeTag"
                            variant="secondary"
                            class="gap-1 text-xs"
                        >
                            #{{ activeTagName }}
                            <button
                                class="ml-0.5 rounded-full hover:text-destructive"
                                @click="onSidebarTagChange('')"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </Badge>
                    </div>

                    <div v-if="loading" class="space-y-3">
                        <Skeleton class="h-14 w-full" />
                        <Skeleton class="h-14 w-full" />
                        <Skeleton class="h-14 w-full" />
                    </div>

                    <div
                        v-else-if="
                            !searchQuery.trim() && !activeType && !activeTag
                        "
                        class="flex flex-col items-center justify-center py-16 text-muted-foreground"
                    >
                        <Search class="mb-3 h-10 w-10 opacity-30" />
                        <p class="text-sm">Type to search across your team</p>
                    </div>

                    <div
                        v-else-if="!hasResults"
                        class="flex flex-col items-center justify-center py-16 text-muted-foreground"
                    >
                        <p class="text-sm">
                            No results found<span v-if="searchQuery.trim()">
                                for "{{ searchQuery }}"</span
                            >
                        </p>
                    </div>

                    <div v-else class="space-y-1">
                        <template
                            v-for="category in SEARCH_CATEGORIES"
                            :key="category.key"
                        >
                            <template
                                v-if="localResults[category.key]?.length > 0"
                            >
                                <div
                                    class="flex items-center gap-2 px-1 py-1.5 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    <component
                                        :is="category.icon"
                                        class="h-3 w-3 opacity-70"
                                    />
                                    {{ category.label }}
                                    <span class="font-normal tabular-nums">
                                        ({{
                                            localResults[category.key].length
                                        }})
                                    </span>
                                </div>
                                <div class="space-y-0.5">
                                    <Link
                                        v-for="item in localResults[
                                            category.key
                                        ]"
                                        :key="item.id"
                                        :href="item.url"
                                        class="flex min-w-0 items-start gap-3 overflow-hidden rounded-lg border px-3 py-2.5 text-sm transition-colors hover:bg-accent/50"
                                    >
                                        <component
                                            :is="category.icon"
                                            class="mt-0.5 h-4 w-4 shrink-0 opacity-60"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="font-medium [overflow-wrap:anywhere]"
                                            >
                                                {{ item.title }}
                                            </p>
                                            <p
                                                v-if="item.subtitle"
                                                class="text-xs [overflow-wrap:anywhere] text-muted-foreground"
                                            >
                                                {{ item.subtitle }}
                                            </p>
                                        </div>
                                    </Link>
                                </div>
                                <Separator class="my-2" />
                            </template>
                        </template>
                        <p
                            class="pt-2 text-center text-xs text-muted-foreground"
                        >
                            {{ allResults.length }} result{{
                                allResults.length === 1 ? '' : 's'
                            }}
                        </p>
                    </div>
                </div>

                <div
                    data-testid="search-sidebar"
                    class="order-1 w-full shrink-0 bg-background/60 px-2 select-none xl:sticky xl:top-8 xl:order-2 xl:max-h-[calc(100svh-4rem)] xl:w-[280px] xl:self-start xl:overflow-y-auto xl:[scrollbar-gutter:stable]"
                >
                    <SearchFilterSidebar
                        :types="types"
                        :tags="tags"
                        :active-type="activeType"
                        :active-tag="activeTag"
                        @update:active-type="onSidebarTypeChange"
                        @update:active-tag="onSidebarTagChange"
                        @clear-filters="clearFilters"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
