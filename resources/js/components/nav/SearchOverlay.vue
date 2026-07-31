<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    Bookmark,
    CalendarDays,
    Contact,
    CreditCard,
    FileText,
    FolderGit2,
    Layers3,
    ListTodo,
    ScrollText,
    Search,
    Table2,
} from 'lucide-vue-next';
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

type SearchResultItem = {
    id: number;
    title: string;
    subtitle: string | null;
    url: string;
};

type SearchResponse = {
    tasks: SearchResultItem[];
    contacts: SearchResultItem[];
    events: SearchResultItem[];
    projects: SearchResultItem[];
    bookmarks: SearchResultItem[];
    subscriptions: SearchResultItem[];
    notes: SearchResultItem[];
    collections: SearchResultItem[];
    log_entries: SearchResultItem[];
    spreadsheets: SearchResultItem[];
};

const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const query = ref('');
const loading = ref(false);
const emptyResults: SearchResponse = {
    tasks: [],
    contacts: [],
    events: [],
    projects: [],
    bookmarks: [],
    subscriptions: [],
    notes: [],
    collections: [],
    log_entries: [],
    spreadsheets: [],
};
const results = ref<SearchResponse>({ ...emptyResults });
const selectedIndex = ref(0);
const inputRef = ref<{ focus: () => void } | null>(null);

const currentTeamSlug = computed(
    () => page.props.currentTeam?.slug as string | undefined,
);

const allResults = computed(() => [
    ...results.value.tasks.map((item) => ({ ...item, type: 'task' as const })),
    ...results.value.contacts.map((item) => ({
        ...item,
        type: 'contact' as const,
    })),
    ...results.value.events.map((item) => ({
        ...item,
        type: 'event' as const,
    })),
    ...results.value.projects.map((item) => ({
        ...item,
        type: 'project' as const,
    })),
    ...results.value.bookmarks.map((item) => ({
        ...item,
        type: 'bookmark' as const,
    })),
    ...results.value.subscriptions.map((item) => ({
        ...item,
        type: 'subscription' as const,
    })),
    ...results.value.notes.map((item) => ({
        ...item,
        type: 'note' as const,
    })),
    ...results.value.collections.map((item) => ({
        ...item,
        type: 'collection' as const,
    })),
    ...results.value.log_entries.map((item) => ({
        ...item,
        type: 'log_entry' as const,
    })),
    ...results.value.spreadsheets.map((item) => ({
        ...item,
        type: 'spreadsheet' as const,
    })),
]);

const hasResults = computed(() => allResults.value.length > 0);

const searchPageUrl = computed(() => {
    if (!currentTeamSlug.value) {
        return '';
    }

    const q = query.value.trim();

    return q
        ? `/${currentTeamSlug.value}/search/page?q=${encodeURIComponent(q)}`
        : `/${currentTeamSlug.value}/search/page`;
});

function goToSearchPage(): void {
    open.value = false;
    router.visit(searchPageUrl.value);
}

const categories: {
    key: keyof SearchResponse;
    label: string;
    icon: typeof Search;
}[] = [
    { key: 'tasks', label: 'Tasks', icon: ListTodo },
    { key: 'contacts', label: 'Contacts', icon: Contact },
    { key: 'events', label: 'Events', icon: CalendarDays },
    { key: 'projects', label: 'Projects', icon: FolderGit2 },
    { key: 'bookmarks', label: 'Bookmarks', icon: Bookmark },
    { key: 'subscriptions', label: 'Subscriptions', icon: CreditCard },
    { key: 'notes', label: 'Notes', icon: FileText },
    { key: 'collections', label: 'Collections', icon: Layers3 },
    { key: 'log_entries', label: 'Log', icon: ScrollText },
    { key: 'spreadsheets', label: 'Spreadsheets', icon: Table2 },
];

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(query, (newQuery) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    selectedIndex.value = 0;

    if (!newQuery.trim() || !currentTeamSlug.value) {
        results.value = { ...emptyResults };
        loading.value = false;

        return;
    }

    loading.value = true;

    debounceTimer = setTimeout(async () => {
        try {
            const response = await fetch(
                `/${currentTeamSlug.value}/search?q=${encodeURIComponent(newQuery.trim())}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            if (response.ok) {
                results.value = await response.json();
            } else {
                results.value = { ...emptyResults };
            }
        } catch {
            results.value = { ...emptyResults };
        } finally {
            loading.value = false;
        }
    }, 200);
});

watch(open, (isOpen) => {
    if (isOpen) {
        query.value = '';
        results.value = { ...emptyResults };
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
    categoryKey: keyof SearchResponse,
    itemIndex: number,
): number {
    let offset = 0;

    for (const cat of categories) {
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
                subscriptions, notes, files, and log entries in your team.
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
                        v-for="category in categories"
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
