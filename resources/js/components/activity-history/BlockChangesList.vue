<script setup lang="ts">
import { ref, watch } from 'vue';
import ExcalidrawEditor from '@/components/excalidraw/ExcalidrawEditor.vue';
import type { BlockChanges, BlockPayload } from '@/types';
import { drawingToggleBtnClass } from './drawing-toggle-button-style';
import TextDiff from './TextDiff.vue';

type Props = {
    activityId: number;
    blockChanges: BlockChanges;
    dialogOpen: boolean;
};

const props = defineProps<Props>();

const expandedDrawings = ref<Record<string, 'old' | 'new' | null>>({});

watch(
    () => props.dialogOpen,
    (isOpen) => {
        if (!isOpen) {
            expandedDrawings.value = {};
        }
    },
);

function blockTypeLabel(type: string): string {
    if (type === 'text') {
        return 'Text';
    }

    if (type === 'excalidraw') {
        return 'Drawing';
    }

    if (type === 'mermaid') {
        return 'Diagram';
    }

    return type;
}

function extractTextContent(payload: BlockPayload | undefined): string {
    if (!payload || typeof payload !== 'object') {
        return '';
    }

    const content = (payload as Record<string, unknown>).content;

    return typeof content === 'string' ? content : '';
}

function extractExcalidrawScene(payload: BlockPayload | undefined): unknown {
    if (!payload || typeof payload !== 'object') {
        return null;
    }

    return (payload as Record<string, unknown>).scene ?? null;
}

function drawingKey(blockIdx: number): string {
    return `${props.activityId}-${blockIdx}`;
}

function isExcalidraw(type: string): boolean {
    return type === 'excalidraw';
}

function toggleSingle(blockIdx: number): void {
    const key = drawingKey(blockIdx);
    expandedDrawings.value[key] =
        expandedDrawings.value[key] === 'new' ? null : 'new';
}

function isSingleExpanded(blockIdx: number): boolean {
    return expandedDrawings.value[drawingKey(blockIdx)] != null;
}

function toggleBeforeAfter(blockIdx: number, which: 'old' | 'new'): void {
    const key = drawingKey(blockIdx);
    expandedDrawings.value[key] =
        expandedDrawings.value[key] === which ? null : which;
}

function isBeforeAfterExpanded(
    blockIdx: number,
    which: 'old' | 'new',
): boolean {
    return expandedDrawings.value[drawingKey(blockIdx)] === which;
}
</script>

<template>
    <div class="space-y-2">
        <template v-if="blockChanges.added?.length">
            <div
                v-for="(block, idx) in blockChanges.added"
                :key="`a-${idx}`"
                class="min-w-0 rounded-md border bg-card p-2"
            >
                <div
                    class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Added — {{ blockTypeLabel(block.type) }} #{{
                        block.position + 1
                    }}
                </div>
                <template v-if="isExcalidraw(block.type)">
                    <button
                        type="button"
                        :class="[
                            'inline-flex cursor-pointer items-center rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                            drawingToggleBtnClass(isSingleExpanded(idx)),
                        ]"
                        @click="toggleSingle(idx)"
                    >
                        {{ isSingleExpanded(idx) ? 'Hide' : 'Show' }}
                    </button>
                    <div
                        v-if="isSingleExpanded(idx)"
                        class="excalidraw-preview mt-1.5 min-w-0 overflow-hidden rounded-lg border"
                    >
                        <ExcalidrawEditor
                            :model-value="
                                extractExcalidrawScene(block.payload) as any
                            "
                            :readonly="true"
                            name="New"
                            height="150px"
                        />
                    </div>
                </template>
                <TextDiff
                    v-else
                    old-text=""
                    :new-text="extractTextContent(block.payload)"
                />
            </div>
        </template>

        <template v-if="blockChanges.updated?.length">
            <div
                v-for="(block, idx) in blockChanges.updated"
                :key="`u-${idx}`"
                class="min-w-0 rounded-md border bg-card p-2"
            >
                <div
                    class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Updated — {{ blockTypeLabel(block.type) }} #{{
                        block.position + 1
                    }}
                </div>
                <template v-if="isExcalidraw(block.type)">
                    <div class="mb-1 flex items-center gap-1.5">
                        <button
                            type="button"
                            :class="[
                                'inline-flex cursor-pointer items-center rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                                drawingToggleBtnClass(
                                    isBeforeAfterExpanded(idx, 'old'),
                                ),
                            ]"
                            @click="toggleBeforeAfter(idx, 'old')"
                        >
                            Before
                        </button>
                        <button
                            type="button"
                            :class="[
                                'inline-flex cursor-pointer items-center rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                                drawingToggleBtnClass(
                                    isBeforeAfterExpanded(idx, 'new'),
                                ),
                            ]"
                            @click="toggleBeforeAfter(idx, 'new')"
                        >
                            After
                        </button>
                    </div>
                    <div
                        v-if="isBeforeAfterExpanded(idx, 'old')"
                        class="excalidraw-preview min-w-0 overflow-hidden rounded-lg border"
                    >
                        <ExcalidrawEditor
                            :model-value="
                                extractExcalidrawScene(block.old_payload) as any
                            "
                            :readonly="true"
                            name="Before"
                            height="150px"
                        />
                    </div>
                    <div
                        v-if="isBeforeAfterExpanded(idx, 'new')"
                        class="excalidraw-preview min-w-0 overflow-hidden rounded-lg border"
                    >
                        <ExcalidrawEditor
                            :model-value="
                                extractExcalidrawScene(block.payload) as any
                            "
                            :readonly="true"
                            name="After"
                            height="150px"
                        />
                    </div>
                </template>
                <TextDiff
                    v-else
                    :old-text="extractTextContent(block.old_payload)"
                    :new-text="extractTextContent(block.payload)"
                />
            </div>
        </template>

        <template v-if="blockChanges.removed?.length">
            <div
                v-for="(block, idx) in blockChanges.removed"
                :key="`r-${idx}`"
                class="min-w-0 rounded-md border bg-card p-2"
            >
                <div
                    class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Removed — {{ blockTypeLabel(block.type) }} #{{
                        block.position + 1
                    }}
                </div>
                <template v-if="isExcalidraw(block.type)">
                    <button
                        type="button"
                        :class="[
                            'inline-flex cursor-pointer items-center rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                            drawingToggleBtnClass(isSingleExpanded(idx)),
                        ]"
                        @click="toggleSingle(idx)"
                    >
                        {{ isSingleExpanded(idx) ? 'Hide' : 'Show' }}
                    </button>
                    <div
                        v-if="isSingleExpanded(idx)"
                        class="excalidraw-preview mt-1.5 min-w-0 overflow-hidden rounded-lg border"
                    >
                        <ExcalidrawEditor
                            :model-value="
                                extractExcalidrawScene(block.payload) as any
                            "
                            :readonly="true"
                            name="Removed"
                            height="150px"
                        />
                    </div>
                </template>
                <TextDiff
                    v-else
                    :old-text="extractTextContent(block.payload)"
                    new-text=""
                />
            </div>
        </template>
    </div>
</template>
