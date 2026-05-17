<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import ExcalidrawEditor from '@/components/excalidraw/ExcalidrawEditor.vue';
import { formatDateTime } from '@/lib/utils';
import type { ActivityHistoryItem } from '@/types';
import BlockChangesList from './BlockChangesList.vue';
import { drawingToggleBtnClass } from './drawing-toggle-button-style';
import TextDiff from './TextDiff.vue';

type Props = {
    activities: ActivityHistoryItem[];
    total?: number;
    hasMore?: boolean;
    loading?: boolean;
    dialogOpen?: boolean;
};

const emit = defineEmits<{
    'load-more': [];
}>();

const props = withDefaults(defineProps<Props>(), {
    total: 0,
    hasMore: false,
    loading: false,
    dialogOpen: false,
});

const expandedDrawings = ref<Record<number, 'old' | 'new' | null>>({});

watch(
    () => props.dialogOpen,
    (isOpen) => {
        if (!isOpen) {
            expandedDrawings.value = {};
        }
    },
);
const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const EVENT_LABELS: Record<string, string> = {
    blocks_updated: 'Content updated',
};

function eventLabel(event: string | null): string {
    if (!event) {
        return 'Activity';
    }

    return EVENT_LABELS[event] ?? fieldLabel(event);
}

function fieldLabel(field: string): string {
    return field.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase());
}

function stripHtml(html: string): string {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;

    return tmp.textContent || tmp.innerText || '';
}

function getFieldValue(
    activity: ActivityHistoryItem,
    field: string,
    side: 'old' | 'new',
): string {
    const raw =
        side === 'old' ? activity.old?.[field] : activity.attributes?.[field];

    if (raw === null || raw === undefined) {
        return '';
    }

    let text = typeof raw === 'string' ? raw : JSON.stringify(raw);

    if (field === 'body' || field === 'description') {
        text = stripHtml(text);
    }

    return text;
}

function getDiffValue(
    activity: ActivityHistoryItem,
    field: string,
    side: 'old' | 'new',
): string {
    const raw =
        side === 'old' ? activity.old?.[field] : activity.attributes?.[field];

    if (isArrayField(field)) {
        return raw === null || raw === undefined
            ? ''
            : formatEntryList(raw).join('\n');
    }

    return getFieldValue(activity, field, side);
}

function isDrawingField(field: string): boolean {
    return field === 'drawing_data';
}

function isArrayField(field: string): boolean {
    return (
        field === 'phone_numbers' ||
        field === 'email_addresses' ||
        field === 'links'
    );
}

function formatEntryList(value: unknown): string[] {
    if (value === null || value === undefined) {
        return ['None'];
    }

    if (!Array.isArray(value)) {
        return [typeof value === 'string' ? value : JSON.stringify(value)];
    }

    if (value.length === 0) {
        return ['None'];
    }

    return value.map((entry) => {
        if (entry && typeof entry === 'object' && 'value' in entry) {
            const label = (entry as Record<string, string>).label;
            const val = (entry as Record<string, string>).value;

            return label ? `${label}: ${val}` : val;
        }

        return JSON.stringify(entry);
    });
}

function relationChangeTargetUrl(
    activity: ActivityHistoryItem,
): string | undefined {
    const target = activity.relationChanges?.target;

    if (target && typeof target === 'object' && 'url' in target) {
        return (target as Record<string, string>).url;
    }

    return undefined;
}

function toggleDrawing(activityId: number, which: 'old' | 'new'): void {
    const current = expandedDrawings.value[activityId];
    expandedDrawings.value[activityId] = current === which ? null : which;
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting && props.hasMore && !props.loading) {
                emit('load-more');
            }
        },
        { rootMargin: '200px' },
    );
});

watch(
    () => props.loading,
    (loading, wasLoading) => {
        if (
            wasLoading &&
            !loading &&
            props.hasMore &&
            sentinel.value &&
            observer
        ) {
            observer.unobserve(sentinel.value);
            observer.observe(sentinel.value);
        }
    },
);

watch(sentinel, (el, _oldEl, onCleanup) => {
    if (observer && el) {
        observer.observe(el);
    }

    onCleanup(() => {
        if (observer && el) {
            observer.unobserve(el);
        }
    });
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});
</script>

<template>
    <div
        v-if="activities.length === 0 && !loading"
        class="text-sm text-muted-foreground"
    >
        No activity yet.
    </div>

    <div v-else class="min-w-0 space-y-5">
        <div
            v-for="(activity, index) in activities"
            :key="activity.id"
            class="space-y-2 border-b pb-4 last:border-0"
        >
            <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-medium">
                    {{ total - index }}/{{ total }}:
                    {{ eventLabel(activity.event) }}
                </span>
                <span class="text-xs text-muted-foreground">
                    {{ formatDateTime(activity.createdAt) }}
                </span>
            </div>

            <div
                v-if="activity.causerName"
                class="text-xs text-muted-foreground"
            >
                by {{ activity.causerName }}
            </div>

            <div v-if="activity.relationChanges" class="space-y-1.5">
                <div class="text-sm">
                    <Link
                        v-if="relationChangeTargetUrl(activity)"
                        :href="relationChangeTargetUrl(activity)"
                        class="font-medium text-foreground hover:underline"
                    >
                        {{ activity.description }}
                    </Link>
                    <span v-else>
                        {{ activity.description }}
                    </span>
                </div>

                <div
                    v-if="
                        activity.relationChanges.action === 'sync' &&
                        (activity.relationChanges.added?.length ||
                            activity.relationChanges.removed?.length)
                    "
                    class="space-y-1"
                >
                    <div
                        v-if="activity.relationChanges.added?.length"
                        class="flex flex-wrap items-center gap-1"
                    >
                        <span
                            class="text-[10px] font-medium text-muted-foreground uppercase"
                        >
                            Added
                        </span>
                        <TextDiff
                            v-for="name in activity.relationChanges.added"
                            :key="name"
                            old-text=""
                            :new-text="name"
                        />
                    </div>
                    <div
                        v-if="activity.relationChanges.removed?.length"
                        class="flex flex-wrap items-center gap-1"
                    >
                        <span
                            class="text-[10px] font-medium text-muted-foreground uppercase"
                        >
                            Removed
                        </span>
                        <TextDiff
                            v-for="name in activity.relationChanges.removed"
                            :key="name"
                            :old-text="name"
                            new-text=""
                        />
                    </div>
                </div>
            </div>

            <div v-else-if="activity.blockChanges" class="space-y-1.5">
                <div class="text-sm">
                    {{ activity.description }}
                </div>

                <BlockChangesList
                    :activity-id="activity.id"
                    :block-changes="activity.blockChanges"
                    :dialog-open="props.dialogOpen"
                />
            </div>

            <div
                v-else-if="activity.changedFields.length > 0"
                class="space-y-2"
            >
                <div
                    v-for="field in activity.changedFields"
                    :key="field"
                    class="min-w-0 rounded-md border bg-card p-2"
                >
                    <div
                        class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ fieldLabel(field) }}
                    </div>

                    <div v-if="isDrawingField(field)" class="space-y-2">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                :class="[
                                    'inline-flex cursor-pointer items-center rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                                    drawingToggleBtnClass(
                                        expandedDrawings[activity.id] === 'old',
                                    ),
                                ]"
                                @click="toggleDrawing(activity.id, 'old')"
                            >
                                Before
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'inline-flex cursor-pointer items-center rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                                    drawingToggleBtnClass(
                                        expandedDrawings[activity.id] === 'new',
                                    ),
                                ]"
                                @click="toggleDrawing(activity.id, 'new')"
                            >
                                After
                            </button>
                        </div>

                        <div
                            v-if="expandedDrawings[activity.id] === 'old'"
                            class="excalidraw-preview min-w-0 overflow-hidden rounded-lg border"
                        >
                            <ExcalidrawEditor
                                :model-value="
                                    (activity.old?.[field] as any) ?? null
                                "
                                :readonly="true"
                                name="Before"
                                height="150px"
                            />
                        </div>

                        <div
                            v-if="expandedDrawings[activity.id] === 'new'"
                            class="excalidraw-preview min-w-0 overflow-hidden rounded-lg border"
                        >
                            <ExcalidrawEditor
                                :model-value="
                                    (activity.attributes?.[field] as any) ??
                                    null
                                "
                                :readonly="true"
                                name="After"
                                height="150px"
                            />
                        </div>
                    </div>

                    <TextDiff
                        v-else
                        :old-text="getDiffValue(activity, field, 'old')"
                        :new-text="getDiffValue(activity, field, 'new')"
                    />
                </div>
            </div>

            <div
                v-else-if="activity.description"
                class="text-sm text-muted-foreground"
            >
                {{ activity.description }}
            </div>
        </div>

        <div
            v-if="activities.length > 0"
            ref="sentinel"
            class="flex items-center justify-center py-4"
        >
            <div
                v-if="hasMore"
                class="h-5 w-5 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent"
            />
            <span v-else class="text-xs text-muted-foreground">
                End of activity history
            </span>
        </div>
    </div>
</template>
