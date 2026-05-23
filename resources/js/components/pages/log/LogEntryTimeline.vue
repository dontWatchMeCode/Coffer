<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import {
    Clipboard,
    ClipboardCheck,
    MessageSquareText,
    Trash2,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { formatRelativeTime } from '@/lib/tasks';
import type { LogEntryItem, LogTimelineItem } from '@/types';

type Props = {
    entriesWithSeparators: LogTimelineItem[];
    hasActiveFilters: boolean;
    copiedEntryId: number | null;
};

defineProps<Props>();

const emit = defineEmits<{
    copy: [entry: LogEntryItem];
    delete: [entry: LogEntryItem];
}>();

function formatEntryTime(dateString: string | null | undefined): string {
    if (!dateString) {
        return '';
    }

    return new Date(dateString).toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
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
                                    @click.stop="emit('copy', item.entry)"
                                >
                                    <ClipboardCheck
                                        v-if="copiedEntryId === item.entry.id"
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
                                    @click="emit('delete', item.entry)"
                                >
                                    <Trash2
                                        class="h-3.5 w-3.5 text-muted-foreground"
                                    />
                                </Button>
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
