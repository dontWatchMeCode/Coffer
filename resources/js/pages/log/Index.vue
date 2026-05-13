<script setup lang="ts">
/* eslint-disable max-lines */
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CheckIcon,
    Clipboard,
    ClipboardCheck,
    MessageSquareText,
    Plus,
    SendHorizontal,
    Trash2,
} from 'lucide-vue-next';
import {
    ListboxContent,
    ListboxFilter,
    ListboxItem,
    ListboxItemIndicator,
    ListboxRoot,
} from 'reka-ui';
import type { AcceptableInputValue, AcceptableValue } from 'reka-ui';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    TagsInput,
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
} from '@/components/ui/tags-input';
import { serializeLogEntry } from '@/lib/markdown-serializers';
import { formatRelativeTime } from '@/lib/tasks';
import { destroy as deleteEntry, index as logIndex } from '@/routes/team/log';
import type { LogEntryItem, Team } from '@/types';

type Props = {
    entries: LogEntryItem[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const body = ref('');
const searchQuery = ref('');
const category = ref('');
const categorySearch = ref('');
const categoryDropdownOpen = ref(false);
const selectedCategory = ref<string | null>(null);
const scrollContainer = ref<HTMLElement | null>(null);
const categoryPicker = ref<HTMLElement | null>(null);
const isSubmitting = ref(false);
const copiedEntryId = ref<number | null>(null);

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

const categoryOptions = computed(() => {
    const categories = new Map<string, string>();

    for (const entry of props.entries) {
        const entryCategory = entry.category?.trim();

        if (entryCategory) {
            categories.set(entryCategory.toLowerCase(), entryCategory);
        }
    }

    return [...categories.values()].sort((a, b) => a.localeCompare(b));
});

const composerCategoryValues = computed(() =>
    category.value ? [category.value] : [],
);

const trimmedCategorySearch = computed(() => categorySearch.value.trim());

const matchingCategoryOptions = computed(() => {
    const search = trimmedCategorySearch.value.toLowerCase();

    if (!search) {
        return categoryOptions.value;
    }

    return categoryOptions.value.filter((option) =>
        option.toLowerCase().includes(search),
    );
});

const hasExactCategoryOption = computed(() =>
    categoryOptions.value.some(
        (option) =>
            option.toLowerCase() === trimmedCategorySearch.value.toLowerCase(),
    ),
);

const canCreateCategory = computed(
    () => trimmedCategorySearch.value !== '' && !hasExactCategoryOption.value,
);

const filteredEntries = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.entries.filter((entry) => {
        if (
            selectedCategory.value &&
            entry.category !== selectedCategory.value
        ) {
            return false;
        }

        if (!query) {
            return true;
        }

        return (
            entry.body.toLowerCase().includes(query) ||
            entry.category?.toLowerCase().includes(query) === true
        );
    });
});

const hasActiveFilters = computed(
    () => searchQuery.value.trim() !== '' || selectedCategory.value !== null,
);

function clearFilters(): void {
    searchQuery.value = '';
    selectedCategory.value = null;
}

function scrollToBottom(): void {
    nextTick(() => {
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop =
                scrollContainer.value.scrollHeight;
        }
    });
}

onMounted(scrollToBottom);
watch(() => props.entries.length, scrollToBottom);
watch(categoryOptions, (options) => {
    if (selectedCategory.value && !options.includes(selectedCategory.value)) {
        selectedCategory.value = null;
    }
});

function handleCategoryFocusOut(event: FocusEvent): void {
    const nextTarget = event.relatedTarget;

    if (
        nextTarget instanceof Node &&
        categoryPicker.value?.contains(nextTarget)
    ) {
        return;
    }

    categoryDropdownOpen.value = false;
}

function submit(): void {
    const trimmed = body.value.trim();
    const submittedCategory = category.value.trim() || selectedCategory.value;

    if (!trimmed || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    body.value = '';

    router.post(
        logIndex(currentTeamSlug.value).url,
        {
            body: trimmed,
            category: submittedCategory || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submit();
    }
}

function deleteEntryItem(entry: LogEntryItem): void {
    router.delete(
        deleteEntry({
            current_team: currentTeamSlug.value,
            logEntry: entry.id,
        }).url,
        { preserveScroll: true },
    );
}

function setComposerCategory(value: string): void {
    category.value = value;
    categorySearch.value = '';
    categoryDropdownOpen.value = false;
}

function handleComposerCategoryModel(nextValues: AcceptableInputValue[]): void {
    const nextCategory = nextValues.at(-1);

    category.value = nextCategory ? String(nextCategory) : '';
}

function handleComposerCategorySelect(value: AcceptableValue): void {
    if (Array.isArray(value) || value === null || value === undefined) {
        return;
    }

    setComposerCategory(String(value));
}

function handleCategoryCreate(): void {
    if (canCreateCategory.value) {
        setComposerCategory(trimmedCategorySearch.value);
    }
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

function formatEntryTime(dateString: string | null | undefined): string {
    if (!dateString) {
        return '';
    }

    return new Date(dateString).toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
}

type SeparatorItem = { type: 'separator'; label: string; key: string };
type EntryItem = { type: 'entry'; entry: LogEntryItem; key: string };

const entriesWithSeparators = computed<(SeparatorItem | EntryItem)[]>(() => {
    const result: (SeparatorItem | EntryItem)[] = [];
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

defineOptions({
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

    <div
        class="flex h-[calc(100svh-4rem-16px)] min-h-0 flex-col overflow-hidden"
    >
        <PageHeader title="Log" description="Quick notes and thoughts." />

        <div
            ref="scrollContainer"
            class="min-h-0 flex-1 overflow-y-auto px-4 py-6"
        >
            <div class="mx-auto flex min-h-full max-w-3xl flex-col gap-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <SearchInput
                            v-model="searchQuery"
                            placeholder="Search log..."
                            data-testid="log-search-input"
                        />
                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="shrink-0 cursor-pointer"
                            @click="clearFilters"
                        >
                            Clear
                        </Button>
                    </div>

                    <div
                        v-if="categoryOptions.length > 0"
                        class="flex flex-wrap items-center gap-2"
                    >
                        <button
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs transition-colors"
                            :class="
                                selectedCategory === null
                                    ? 'border-foreground/20 bg-muted text-foreground'
                                    : 'border-border text-muted-foreground hover:text-foreground'
                            "
                            @click="selectedCategory = null"
                        >
                            All
                        </button>
                        <button
                            v-for="option in categoryOptions"
                            :key="option"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs transition-colors"
                            :class="
                                selectedCategory === option
                                    ? 'border-foreground/20 bg-muted text-foreground'
                                    : 'border-border text-muted-foreground hover:text-foreground'
                            "
                            @click="selectedCategory = option"
                        >
                            {{ option }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="filteredEntries.length > 0"
                    class="relative space-y-5 pl-7 before:absolute before:top-7 before:bottom-2 before:left-2 before:w-px before:bg-border"
                >
                    <template
                        v-for="item in entriesWithSeparators"
                        :key="item.key"
                    >
                        <div
                            v-if="item.type === 'separator'"
                            class="relative -ml-7 flex items-center gap-3 py-1"
                        >
                            <span class="h-px flex-1 bg-border"></span>
                            <span
                                class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                {{ item.label }}
                            </span>
                            <span class="h-px flex-1 bg-border"></span>
                        </div>

                        <article v-else class="group relative">
                            <span
                                class="absolute top-1.5 -left-[1.65rem] h-2.5 w-2.5 rounded-full border border-border bg-background ring-4 ring-background"
                                aria-hidden="true"
                            ></span>
                            <div class="flex items-start gap-4">
                                <time
                                    class="w-16 shrink-0 pt-0.5 text-right text-xs text-muted-foreground tabular-nums"
                                    :title="item.entry.createdAt ?? ''"
                                >
                                    {{ formatEntryTime(item.entry.createdAt) }}
                                </time>
                                <div class="min-w-0 flex-1 border-b pb-5">
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <p
                                            class="text-sm leading-relaxed whitespace-pre-wrap"
                                        >
                                            {{ item.entry.body }}
                                        </p>

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-7 w-7 shrink-0 cursor-pointer opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                                            aria-label="Copy as Markdown"
                                            @click.stop="
                                                copyLogEntry(item.entry)
                                            "
                                        >
                                            <ClipboardCheck
                                                v-if="
                                                    copiedEntryId ===
                                                    item.entry.id
                                                "
                                                class="h-3.5 w-3.5 text-green-600"
                                            />
                                            <Clipboard
                                                v-else
                                                class="h-3.5 w-3.5 text-muted-foreground"
                                            />
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-7 w-7 shrink-0 cursor-pointer opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                                            aria-label="Delete entry"
                                            @click="deleteEntryItem(item.entry)"
                                        >
                                            <Trash2
                                                class="h-3.5 w-3.5 text-muted-foreground"
                                            />
                                        </Button>
                                    </div>
                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                                    >
                                        <span>
                                            {{
                                                formatRelativeTime(
                                                    item.entry.createdAt,
                                                )
                                            }}
                                        </span>
                                        <span
                                            v-if="item.entry.category"
                                            class="rounded-full border border-border px-2 py-0.5 text-[11px]"
                                        >
                                            {{ item.entry.category }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>

                <div
                    v-else
                    class="flex flex-1 items-center justify-center py-16 text-center"
                >
                    <div class="max-w-sm">
                        <MessageSquareText
                            class="mx-auto h-10 w-10 text-muted-foreground/50"
                        />
                        <p class="mt-4 font-medium">
                            {{
                                hasActiveFilters
                                    ? 'No matching entries.'
                                    : "Start today's log."
                            }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            <template v-if="hasActiveFilters">
                                Try a different search or category.
                            </template>
                            <template v-else>
                                Drop quick notes here as the day moves. They'll
                                stack into a timeline.
                            </template>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="shrink-0 border-t px-4 py-3">
            <div
                class="mx-auto flex max-w-3xl flex-col gap-2 sm:flex-row sm:items-end"
            >
                <ListboxRoot
                    :model-value="category || undefined"
                    highlight-on-hover
                    @update:model-value="handleComposerCategorySelect"
                >
                    <div
                        ref="categoryPicker"
                        class="relative w-full sm:w-52"
                        @focusout="handleCategoryFocusOut"
                    >
                        <TagsInput
                            :model-value="composerCategoryValues"
                            class="min-h-[44px] w-full border-input bg-transparent shadow-sm"
                            @update:model-value="handleComposerCategoryModel"
                        >
                            <TagsInputItem
                                v-if="category"
                                :value="category"
                                :disabled="isSubmitting"
                            >
                                <TagsInputItemText />
                                <TagsInputItemDelete />
                            </TagsInputItem>
                            <ListboxFilter v-model="categorySearch" as-child>
                                <TagsInputInput
                                    v-if="!category"
                                    placeholder="Category"
                                    :disabled="isSubmitting"
                                    @focus="categoryDropdownOpen = true"
                                    @keydown.enter.prevent="
                                        handleCategoryCreate
                                    "
                                    @keydown.down="categoryDropdownOpen = true"
                                />
                            </ListboxFilter>
                        </TagsInput>

                        <div
                            v-if="categoryDropdownOpen"
                            class="absolute bottom-full z-50 mb-1 w-full rounded-lg border bg-popover p-1 text-popover-foreground shadow-md"
                        >
                            <ListboxContent
                                class="max-h-[220px] scroll-py-1 overflow-x-hidden overflow-y-auto"
                                tabindex="0"
                            >
                                <ListboxItem
                                    v-for="option in matchingCategoryOptions"
                                    :key="option"
                                    class="relative flex cursor-default items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-hidden select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                                    :value="option"
                                >
                                    <span class="truncate">{{ option }}</span>
                                    <ListboxItemIndicator
                                        class="ml-auto inline-flex items-center justify-center"
                                    >
                                        <CheckIcon class="h-4 w-4" />
                                    </ListboxItemIndicator>
                                </ListboxItem>

                                <button
                                    v-if="canCreateCategory"
                                    type="button"
                                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    @click="handleCategoryCreate"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    Create "{{ trimmedCategorySearch }}"
                                </button>

                                <div
                                    v-if="
                                        !canCreateCategory &&
                                        matchingCategoryOptions.length === 0
                                    "
                                    class="px-2 py-1.5 text-xs text-muted-foreground"
                                >
                                    No categories found.
                                </div>
                            </ListboxContent>
                        </div>
                    </div>
                </ListboxRoot>
                <div class="flex items-end gap-2 sm:flex-1">
                    <textarea
                        v-model="body"
                        placeholder="Log a note..."
                        class="max-h-[160px] min-h-[44px] w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        :rows="1"
                        :disabled="isSubmitting"
                        @keydown="handleKeydown"
                    ></textarea>
                    <Button
                        size="icon"
                        class="h-[44px] w-[44px] shrink-0 cursor-pointer"
                        :disabled="!body.trim() || isSubmitting"
                        title="Add log entry"
                        @click="submit"
                    >
                        <SendHorizontal class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <p class="mx-auto mt-2 max-w-3xl text-xs text-muted-foreground">
                Press Enter to log. Shift+Enter for a new line.
            </p>
        </div>
    </div>
</template>
