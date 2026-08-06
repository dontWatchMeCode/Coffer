<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import SearchPrefixTooltip from '@/components/search/SearchPrefixTooltip.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { useTeamSearch } from '@/composables/useTeamSearch';
import { SEARCH_CATEGORIES } from '@/lib/search';
import type { SearchCategoryKey } from '@/lib/search';

const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const query = ref('');
const selectedIndex = ref(0);
const inputRef = ref<{ focus: () => void } | null>(null);

const currentTeamSlug = computed(
    () => page.props.currentTeam?.slug as string | undefined,
);

const {
    loading,
    results,
    allResults,
    hasResults,
    resetResults,
    searchPageUrl: buildTeamSearchPageUrl,
    runJsonSearch,
} = useTeamSearch(currentTeamSlug);

const searchPageUrl = computed(() => {
    const q = query.value.trim();

    return buildTeamSearchPageUrl(q ? { q } : {});
});

function goToSearchPage(): void {
    open.value = false;
    router.visit(searchPageUrl.value);
}

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(query, (newQuery) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    selectedIndex.value = 0;

    if (!newQuery.trim() || !currentTeamSlug.value) {
        resetResults();

        return;
    }

    loading.value = true;

    debounceTimer = setTimeout(() => {
        runJsonSearch({ q: newQuery.trim() });
    }, 200);
});

watch(open, (isOpen) => {
    if (isOpen) {
        query.value = '';
        resetResults();
        selectedIndex.value = 0;
        nextTick(() => inputRef.value?.focus());
    }
});

function onGlobalKeyDown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();

        if (open.value) {
            open.value = false;
            router.visit(searchPageUrl.value);
        } else {
            open.value = true;
        }
    }
}

onMounted(() => {
    document.addEventListener('keydown', onGlobalKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onGlobalKeyDown);
});

function onKeyDown(event: KeyboardEvent): void {
    if (!hasResults.value) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectedIndex.value =
            (selectedIndex.value + 1) % allResults.value.length;
        scrollToSelected();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectedIndex.value =
            (selectedIndex.value - 1 + allResults.value.length) %
            allResults.value.length;
        scrollToSelected();
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const item = allResults.value[selectedIndex.value];

        if (item) {
            navigateTo(item.url);
        }
    }
}

function scrollToSelected(): void {
    nextTick(() => {
        const el = document.querySelector('[data-selected="true"]');
        el?.scrollIntoView({ block: 'nearest' });
    });
}

function navigateTo(url: string): void {
    open.value = false;
    router.visit(url);
}

function getResultIndex(
    categoryKey: SearchCategoryKey,
    itemIndex: number,
): number {
    let offset = 0;

    for (const cat of SEARCH_CATEGORIES) {
        if (cat.key === categoryKey) {
            break;
        }

        offset += results.value[cat.key].length;
    }

    return offset + itemIndex;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="gap-0 overflow-hidden p-0 sm:max-w-lg"
            :showCloseButton="false"
            @keydown="onKeyDown"
        >
            <DialogTitle class="sr-only">Search</DialogTitle>
            <DialogDescription class="sr-only">
                Search across tasks, contacts, events, projects, bookmarks,
                subscriptions, notes, files, collections, log entries, and
                spreadsheets in your team.
            </DialogDescription>

            <div class="flex items-center border-b px-4">
                <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                <Input
                    ref="inputRef"
                    data-testid="global-search-input"
                    v-model="query"
                    placeholder="Search tasks, contacts, events... use #tag to filter by tag"
                    class="h-12 flex-1 border-0 bg-transparent shadow-none focus-visible:ring-0 dark:bg-transparent"
                />
                <SearchPrefixTooltip class="ml-2 shrink-0" />
                <div
                    class="ml-2 flex shrink-0 items-center gap-1 text-xs text-muted-foreground"
                >
                    <kbd class="rounded border bg-muted px-1.5 py-0.5 font-mono"
                        >ESC</kbd
                    >
                </div>
            </div>

            <div class="max-h-[60vh] overflow-y-auto">
                <div v-if="loading" class="space-y-2 p-4">
                    <Skeleton class="h-10 w-full" />
                    <Skeleton class="h-10 w-full" />
                    <Skeleton class="h-10 w-full" />
                </div>

                <div
                    v-else-if="!query.trim()"
                    class="flex flex-col items-center justify-center py-8 text-sm text-muted-foreground"
                >
                    <Search class="mb-2 h-8 w-8 opacity-30" />
                    <p>Type to search across your team</p>
                </div>

                <div
                    v-else-if="!hasResults"
                    class="flex flex-col items-center justify-center py-8 text-sm text-muted-foreground"
                >
                    <p>No results found for "{{ query }}"</p>
                </div>

                <div v-else>
                    <template
                        v-for="category in SEARCH_CATEGORIES"
                        :key="category.key"
                    >
                        <div
                            v-if="results[category.key].length > 0"
                            class="border-t border-border/50 px-2 py-2 first:border-t-0"
                        >
                            <div
                                class="mb-1 flex items-center gap-2 px-2 py-1.5 text-[11px] font-medium tracking-wider text-muted-foreground/80 uppercase"
                            >
                                <component
                                    :is="category.icon"
                                    class="h-3 w-3 opacity-70"
                                />
                                {{ category.label }}
                            </div>
                            <div class="space-y-0.5">
                                <button
                                    v-for="(item, idx) in results[category.key]"
                                    :key="item.id"
                                    data-testid="global-search-result"
                                    :data-selected="
                                        getResultIndex(category.key, idx) ===
                                        selectedIndex
                                    "
                                    class="flex w-full items-start gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                                    :class="{
                                        'bg-accent text-accent-foreground':
                                            getResultIndex(
                                                category.key,
                                                idx,
                                            ) === selectedIndex,
                                        'hover:bg-accent/50':
                                            getResultIndex(
                                                category.key,
                                                idx,
                                            ) !== selectedIndex,
                                    }"
                                    @click="navigateTo(item.url)"
                                    @mouseenter="
                                        selectedIndex = getResultIndex(
                                            category.key,
                                            idx,
                                        )
                                    "
                                >
                                    <component
                                        :is="category.icon"
                                        class="mt-0.5 h-4 w-4 shrink-0 opacity-60"
                                    />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">
                                            {{ item.title }}
                                        </p>
                                        <p
                                            v-if="item.subtitle"
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ item.subtitle }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div
                class="flex items-center justify-between border-t px-4 py-2.5 text-xs text-muted-foreground"
            >
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1">
                        <kbd
                            class="rounded border bg-muted px-1.5 py-0.5 font-mono"
                            >&#8593;</kbd
                        >
                        <kbd
                            class="rounded border bg-muted px-1.5 py-0.5 font-mono"
                            >&#8595;</kbd
                        >
                        to navigate
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd
                            class="rounded border bg-muted px-1.5 py-0.5 font-mono"
                            >&#9166;</kbd
                        >
                        to select
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd
                            class="rounded border bg-muted px-1.5 py-0.5 font-mono"
                            >Ctrl+K</kbd
                        >
                        full page
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        v-if="query.trim()"
                        variant="ghost"
                        size="sm"
                        class="h-6 text-xs"
                        @click="goToSearchPage"
                    >
                        View all
                    </Button>
                    <span v-if="hasResults" class="tabular-nums">
                        {{ allResults.length }} result{{
                            allResults.length === 1 ? '' : 's'
                        }}
                    </span>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
