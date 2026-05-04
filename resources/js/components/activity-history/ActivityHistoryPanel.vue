<script setup lang="ts">
import { History } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ExcalidrawEditor from '@/components/excalidraw/ExcalidrawEditor.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDateTime } from '@/lib/utils';
import type { ActivityHistoryItem } from '@/types';
import TextDiff from './TextDiff.vue';

type Props = {
    activities: ActivityHistoryItem[];
};

const props = defineProps<Props>();

const open = ref(false);
const expandedDrawings = ref<Record<number, 'old' | 'new' | null>>({});

function toggleDrawing(activityId: number, which: 'old' | 'new'): void {
    const current = expandedDrawings.value[activityId];
    expandedDrawings.value[activityId] = current === which ? null : which;
}

watch(open, (isOpen) => {
    if (!isOpen) {
        expandedDrawings.value = {};
    }
});

function eventLabel(event: string | null): string {
    if (!event) {
        return 'Activity';
    }

    return event.charAt(0).toUpperCase() + event.slice(1);
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

    if (field === 'body') {
        text = stripHtml(text);
    }

    return text;
}

function isTextField(field: string): boolean {
    return field === 'title' || field === 'body';
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
</script>

<template>
    <div>
        <button
            type="button"
            class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
            @click="open = true"
        >
            <History class="h-4 w-4 text-muted-foreground" />
            <span class="flex-1">Activity History</span>
            <span
                v-if="props.activities.length > 0"
                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium text-muted-foreground"
            >
                {{ props.activities.length }}
            </span>
        </button>

        <Dialog :open="open" @update:open="open = $event">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Activity History</DialogTitle>
                </DialogHeader>

                <div
                    v-if="props.activities.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No activity yet.
                </div>

                <div v-else class="space-y-5">
                    <div
                        v-for="activity in props.activities"
                        :key="activity.id"
                        class="space-y-2 border-b pb-4 last:border-0"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium">
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

                        <div
                            v-if="activity.changedFields.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="field in activity.changedFields"
                                :key="field"
                                class="rounded-md border bg-card p-2"
                            >
                                <div
                                    class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    {{ fieldLabel(field) }}
                                </div>

                                <div
                                    v-if="isDrawingField(field)"
                                    class="space-y-2"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            :class="[
                                                'inline-flex cursor-pointer items-center rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                                                expandedDrawings[
                                                    activity.id
                                                ] === 'old'
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-border bg-background text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                                            ]"
                                            @click="
                                                toggleDrawing(
                                                    activity.id,
                                                    'old',
                                                )
                                            "
                                        >
                                            Before
                                        </button>
                                        <button
                                            type="button"
                                            :class="[
                                                'inline-flex cursor-pointer items-center rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                                                expandedDrawings[
                                                    activity.id
                                                ] === 'new'
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-border bg-background text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                                            ]"
                                            @click="
                                                toggleDrawing(
                                                    activity.id,
                                                    'new',
                                                )
                                            "
                                        >
                                            After
                                        </button>
                                    </div>

                                    <div
                                        v-if="
                                            expandedDrawings[activity.id] ===
                                            'old'
                                        "
                                        class="excalidraw-preview overflow-hidden rounded-lg border"
                                    >
                                        <ExcalidrawEditor
                                            :model-value="
                                                (activity.old?.[
                                                    field
                                                ] as any) ?? null
                                            "
                                            :readonly="true"
                                            name="Before"
                                            height="150px"
                                        />
                                    </div>

                                    <div
                                        v-if="
                                            expandedDrawings[activity.id] ===
                                            'new'
                                        "
                                        class="excalidraw-preview overflow-hidden rounded-lg border"
                                    >
                                        <ExcalidrawEditor
                                            :model-value="
                                                (activity.attributes?.[
                                                    field
                                                ] as any) ?? null
                                            "
                                            :readonly="true"
                                            name="After"
                                            height="150px"
                                        />
                                    </div>
                                </div>

                                <TextDiff
                                    v-else-if="isTextField(field)"
                                    :old-text="
                                        getFieldValue(activity, field, 'old')
                                    "
                                    :new-text="
                                        getFieldValue(activity, field, 'new')
                                    "
                                />

                                <div
                                    v-else-if="isArrayField(field)"
                                    class="space-y-1"
                                >
                                    <div
                                        v-if="activity.old?.[field] != null"
                                        class="space-y-0.5"
                                    >
                                        <div
                                            v-for="(
                                                line, idx
                                            ) in formatEntryList(
                                                activity.old[field],
                                            )"
                                            :key="`old-${idx}`"
                                            class="text-xs text-muted-foreground line-through"
                                        >
                                            {{ line }}
                                        </div>
                                    </div>
                                    <div
                                        v-if="
                                            activity.attributes?.[field] != null
                                        "
                                        class="space-y-0.5"
                                    >
                                        <div
                                            v-for="(
                                                line, idx
                                            ) in formatEntryList(
                                                activity.attributes[field],
                                            )"
                                            :key="`new-${idx}`"
                                            class="text-xs"
                                        >
                                            {{ line }}
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="space-y-1">
                                    <div
                                        v-if="activity.old?.[field] != null"
                                        class="text-xs text-muted-foreground line-through"
                                    >
                                        {{
                                            getFieldValue(
                                                activity,
                                                field,
                                                'old',
                                            )
                                        }}
                                    </div>
                                    <div
                                        v-if="
                                            activity.attributes?.[field] != null
                                        "
                                        class="text-xs"
                                    >
                                        {{
                                            getFieldValue(
                                                activity,
                                                field,
                                                'new',
                                            )
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
