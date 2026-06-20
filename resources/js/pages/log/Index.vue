<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import SearchInput from '@/components/list/SearchInput.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import LogCategoryFilter from '@/components/pages/log/LogCategoryFilter.vue';
import LogEntryComposer from '@/components/pages/log/LogEntryComposer.vue';
import LogEntryTimeline from '@/components/pages/log/LogEntryTimeline.vue';
import LogTrashButton from '@/components/pages/log/LogTrashButton.vue';
import { Button } from '@/components/ui/button';
import { serializeLogEntry } from '@/lib/markdown-serializers';
import { destroy as deleteEntry, index as logIndex } from '@/routes/team/log';
import type {
    LogEntryItem,
    LogTimelineItem,
    PaginatedData,
    Team,
} from '@/types';

type Props = {
    entries: PaginatedData<LogEntryItem>;
    categories: string[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const urlParams = new URLSearchParams(window.location.search);
const searchQuery = ref<string>(urlParams.get('search') ?? '');
const selectedCategories = ref<string[]>([
    ...(urlParams.get('category') ? [urlParams.get('category') as string] : []),
    ...urlParams.getAll('categories'),
    ...urlParams.getAll('categories[]'),
]);
const suppressFilterVisits = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function submitFilters(): void {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    router.visit(logIndex(currentTeamSlug.value).url, {
        data: {
            search: searchQuery.value || undefined,
            categories:
                selectedCategories.value.length > 0
                    ? selectedCategories.value
                    : undefined,
        },
        only: ['entries', 'categories'],
        reset: ['entries'],
        preserveScroll: true,
        preserveState: true,
    });
}

watch(searchQuery, () => {
    if (suppressFilterVisits.value) {
        return;
    }

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(submitFilters, 300);
});

watch(selectedCategories, () => {
    if (suppressFilterVisits.value) {
        return;
    }

    submitFilters();
});

onUnmounted(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

const copiedEntryId = ref<number | null>(null);
const deleteDialogOpen = ref(false);
const deletingEntry = ref<LogEntryItem | null>(null);

async function copyLogEntry(entry: LogEntryItem): Promise<void> {
    try {
        await navigator.clipboard.writeText(serializeLogEntry(entry));
        copiedEntryId.value = entry.id;
        setTimeout(() => {
            if (copiedEntryId.value === entry.id) {
                copiedEntryId.value = null;
            }
        }, 2000);
    } catch {
        copiedEntryId.value = null;
    }
}

const categoryOptions = computed(() => props.categories);

watch(categoryOptions, (options) => {
    const nextCategories = selectedCategories.value.filter((category) =>
        options.includes(category),
    );

    if (nextCategories.length !== selectedCategories.value.length) {
        selectedCategories.value = nextCategories;
    }
});

function openDeleteDialog(entry: LogEntryItem): void {
    deletingEntry.value = entry;
    deleteDialogOpen.value = true;
}

function confirmDeleteEntry(): void {
    if (!deletingEntry.value) {
        return;
    }

    const entry = deletingEntry.value;
    deleteDialogOpen.value = false;

    router.delete(
        deleteEntry({
            current_team: currentTeamSlug.value,
            logEntry: entry.id,
        }).url,
        { preserveScroll: true },
    );
}

function formatDateSeparator(dateString: string | null | undefined): string {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    }

    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    return date.toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

const filteredEntries = computed(() => props.entries.data);

const entriesWithSeparators = computed<LogTimelineItem[]>(() => {
    const result: LogTimelineItem[] = [];
    let currentLabel = '';

    for (const entry of filteredEntries.value) {
        const label = formatDateSeparator(entry.createdAt);

        if (currentLabel !== label) {
            result.push({ type: 'separator', label, key: `sep-${entry.id}` });
            currentLabel = label;
        }

        result.push({ type: 'entry', entry, key: `entry-${entry.id}` });
    }

    return result;
});

const hasActiveFilters = computed(
    () =>
        searchQuery.value.trim() !== '' || selectedCategories.value.length > 0,
);

function clearFilters(): void {
    suppressFilterVisits.value = true;
    selectedCategories.value = [];
    searchQuery.value = '';

    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }

    submitFilters();

    nextTick(() => {
        suppressFilterVisits.value = false;
    });
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Log',
                href: logIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Log" />

    <div class="flex min-h-[calc(100svh-4rem-16px)] flex-col">
        <PageHeader title="Log" description="Quick notes and thoughts.">
            <template #actions>
                <LogTrashButton
                    :team-slug="currentTeamSlug"
                    class="lg:hidden"
                />
            </template>
        </PageHeader>

        <div class="min-w-0 flex-1 px-4 py-6">
            <div
                class="mx-auto grid w-full max-w-7xl gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]"
            >
                <main class="min-w-0 space-y-5">
                    <LogEntryComposer
                        :categories="categoryOptions"
                        :selected-categories="selectedCategories"
                        :team-slug="currentTeamSlug"
                    />

                    <section>
                        <LogEntryTimeline
                            :entries-with-separators="entriesWithSeparators"
                            :has-active-filters="hasActiveFilters"
                            :copied-entry-id="copiedEntryId"
                            :team-slug="currentTeamSlug"
                            :categories="categoryOptions"
                            @copy="copyLogEntry"
                            @delete="openDeleteDialog"
                        />
                    </section>
                </main>

                <aside class="space-y-6 lg:sticky lg:top-20 lg:self-start">
                    <div class="hidden justify-end lg:flex">
                        <LogTrashButton :team-slug="currentTeamSlug" />
                    </div>

                    <div>
                        <div class="mb-3">
                            <h2 class="text-sm font-semibold">Find entries</h2>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Search the timeline or narrow it by category.
                            </p>
                        </div>

                        <SearchInput
                            v-model="searchQuery"
                            placeholder="Search log..."
                            data-testid="log-search-input"
                        />
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold">Categories</h2>
                        <LogCategoryFilter
                            v-model="selectedCategories"
                            :categories="categoryOptions"
                            class="mt-3"
                        />

                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="mt-3 w-full cursor-pointer justify-center"
                            @click="clearFilters"
                        >
                            Clear
                        </Button>
                    </div>
                </aside>
            </div>
        </div>

        <ConfirmDeleteDialog
            v-model:open="deleteDialogOpen"
            title="Move Log Entry to Trash"
            confirm-label="Move to trash"
            :confirm-icon="Trash2"
            @confirm="confirmDeleteEntry"
        >
            <p v-if="deletingEntry" class="text-sm text-muted-foreground">
                This moves the log entry to trash. You can restore it from log
                trash.
            </p>
            <blockquote
                v-if="deletingEntry"
                class="mt-3 max-h-32 overflow-y-auto rounded-md border bg-muted/30 p-3 text-sm whitespace-pre-wrap"
            >
                {{ deletingEntry.body }}
            </blockquote>
        </ConfirmDeleteDialog>
    </div>
</template>
