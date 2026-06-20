<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import {
    Clipboard,
    ClipboardCheck,
    Pencil,
    MessageSquareText,
    Save,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import LogSingleCategorySelect from '@/components/pages/log/LogSingleCategorySelect.vue';
import { Button } from '@/components/ui/button';
import { formatRelativeTime } from '@/lib/tasks';
import { update as updateLogEntry } from '@/routes/team/log';
import type { LogEntryItem, LogTimelineItem } from '@/types';

type Props = {
    entriesWithSeparators: LogTimelineItem[];
    hasActiveFilters: boolean;
    copiedEntryId: number | null;
    teamSlug: string;
    categories: string[];
};

const props = defineProps<Props>();

const emit = defineEmits<{
    copy: [entry: LogEntryItem];
    delete: [entry: LogEntryItem];
}>();

const editingEntryId = ref<number | null>(null);
const editBody = ref('');
const editCategory = ref('');
const savingEntryId = ref<number | null>(null);

const isSaving = computed(() => savingEntryId.value !== null);

function formatEntryTime(dateString: string | null | undefined): string {
    if (!dateString) {
        return '';
    }

    return new Date(dateString).toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
}

function startEdit(entry: LogEntryItem): void {
    editingEntryId.value = entry.id;
    editBody.value = entry.body;
    editCategory.value = entry.category ?? '';
}

function cancelEdit(): void {
    editingEntryId.value = null;
    editBody.value = '';
    editCategory.value = '';
}

function submitEdit(entry: LogEntryItem): void {
    const trimmedBody = editBody.value.trim();

    if (!trimmedBody || isSaving.value) {
        return;
    }

    savingEntryId.value = entry.id;

    router.patch(
        updateLogEntry({
            current_team: props.teamSlug,
            logEntry: entry.id,
        }).url,
        {
            body: trimmedBody,
            category: editCategory.value.trim() || null,
        },
        {
            preserveScroll: true,
            onSuccess: cancelEdit,
            onFinish: () => {
                savingEntryId.value = null;
            },
        },
    );
}
</script>

<template>
    <InfiniteScroll data="entries">
        <div
            v-if="entriesWithSeparators.length > 0"
            class="relative space-y-5 pl-7 before:absolute before:top-7 before:bottom-2 before:left-2 before:w-px before:bg-border"
        >
            <template v-for="item in entriesWithSeparators" :key="item.key">
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
                            <div class="flex items-start justify-between gap-3">
                                <div
                                    v-if="editingEntryId === item.entry.id"
                                    class="min-w-0 flex-1 space-y-3"
                                >
                                    <textarea
                                        v-model="editBody"
                                        class="min-h-28 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                        :disabled="
                                            savingEntryId === item.entry.id
                                        "
                                    ></textarea>
                                    <LogSingleCategorySelect
                                        v-model="editCategory"
                                        :categories="categories"
                                        :disabled="
                                            savingEntryId === item.entry.id
                                        "
                                        class="w-full sm:max-w-xs"
                                    />
                                    <div class="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            class="cursor-pointer"
                                            :disabled="
                                                !editBody.trim() ||
                                                savingEntryId === item.entry.id
                                            "
                                            @click="submitEdit(item.entry)"
                                        >
                                            <Save class="h-3.5 w-3.5" />
                                            Save
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="cursor-pointer"
                                            :disabled="
                                                savingEntryId === item.entry.id
                                            "
                                            @click="cancelEdit"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                            Cancel
                                        </Button>
                                    </div>
                                </div>

                                <p
                                    v-else
                                    class="text-sm leading-relaxed whitespace-pre-wrap"
                                >
                                    {{ item.entry.body }}
                                </p>

                                <div
                                    class="flex shrink-0 items-center gap-1 opacity-100 transition-opacity sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100"
                                >
                                    <ActivityHistoryPanel
                                        v-if="
                                            item.entry.activityHistory &&
                                            item.entry.activityHistory.total > 0
                                        "
                                        :config="item.entry.activityHistory"
                                        :team-slug="teamSlug"
                                        variant="compact"
                                    />

                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-7 w-7 cursor-pointer"
                                        aria-label="Edit entry"
                                        @click="startEdit(item.entry)"
                                    >
                                        <Pencil
                                            class="h-3.5 w-3.5 text-muted-foreground"
                                        />
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-7 w-7 cursor-pointer"
                                        aria-label="Copy as Markdown"
                                        @click.stop="emit('copy', item.entry)"
                                    >
                                        <ClipboardCheck
                                            v-if="
                                                copiedEntryId === item.entry.id
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
                                        class="h-7 w-7 cursor-pointer"
                                        aria-label="Delete entry"
                                        @click="emit('delete', item.entry)"
                                    >
                                        <Trash2
                                            class="h-3.5 w-3.5 text-muted-foreground"
                                        />
                                    </Button>
                                </div>
                            </div>
                            <div
                                class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                            >
                                <span>{{
                                    formatRelativeTime(item.entry.createdAt)
                                }}</span>
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
                        Drop quick notes here as the day moves. They'll stack
                        into a timeline.
                    </template>
                </p>
            </div>
        </div>
    </InfiniteScroll>
</template>
