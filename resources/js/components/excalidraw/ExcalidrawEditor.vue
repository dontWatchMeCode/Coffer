<script setup lang="ts">
import { Maximize2, Minimize2 } from 'lucide-vue-next';
import React from 'react';
import { createRoot } from 'react-dom/client';
import type { Root } from 'react-dom/client';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { ExcalidrawCanvas } from '@/components/excalidraw/ExcalidrawCanvas';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import type { ExcalidrawScene } from '@/types';

const props = withDefaults(
    defineProps<{
        modelValue?: ExcalidrawScene | null;
        readonly?: boolean;
        hideUi?: boolean;
        name?: string;
        height?: string;
    }>(),
    {
        modelValue: null,
        readonly: false,
        hideUi: false,
        name: 'Drawing',
        height: '620px',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: ExcalidrawScene];
}>();

const isExpanded = ref(false);
const inlineContainer = ref<HTMLDivElement | null>(null);
const expandedContainer = ref<HTMLDivElement | null>(null);
const expandedWrapper = ref<HTMLDivElement | null>(null);
let root: Root | null = null;
let rootElement: HTMLDivElement | null = null;
let canvasComponent: typeof ExcalidrawCanvas | null = null;
let savedFocusElement: Element | null = null;

const { resolvedAppearance } = useAppearance();

function currentContainer(): HTMLDivElement | null {
    return isExpanded.value ? expandedContainer.value : inlineContainer.value;
}

function mountRoot(): void {
    const target = currentContainer();

    if (!target || rootElement === target) {
        return;
    }

    root?.unmount();
    root = createRoot(target);
    rootElement = target;
}

async function renderCanvas(): Promise<void> {
    mountRoot();

    if (!root) {
        return;
    }

    canvasComponent ??= (
        await import('@/components/excalidraw/ExcalidrawCanvas')
    ).ExcalidrawCanvas;

    if (!root) {
        return;
    }

    root.render(
        React.createElement(canvasComponent, {
            initialData: props.modelValue,
            name: props.name,
            readonly: props.readonly,
            hideUi: props.hideUi,
            theme: resolvedAppearance.value,
            onChange: (scene: ExcalidrawScene) =>
                emit('update:modelValue', scene),
        }),
    );
}

onMounted(() => {
    void renderCanvas();
});

watch(
    () => [props.readonly, props.hideUi, props.name],
    () => void renderCanvas(),
);

watch(isExpanded, async (expanded) => {
    document.body.style.overflow = expanded ? 'hidden' : '';

    await nextTick();

    if (expanded) {
        savedFocusElement = document.activeElement;
        expandedWrapper.value?.focus();
    } else if (
        savedFocusElement instanceof HTMLElement &&
        savedFocusElement.isConnected
    ) {
        savedFocusElement.focus();
        savedFocusElement = null;
    }

    void renderCanvas();
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    root?.unmount();
    root = null;
    rootElement = null;
});
</script>

<template>
    <div
        v-show="!isExpanded"
        class="relative overflow-hidden rounded-lg border bg-background"
        :class="{ 'excalidraw-editor--hide-ui': props.hideUi }"
        :style="{
            height: props.height,
            minHeight: props.height === '620px' ? '420px' : undefined,
        }"
    >
        <Button
            type="button"
            variant="secondary"
            size="sm"
            class="absolute top-3 right-3 z-10 shadow-sm"
            aria-label="Expand drawing editor"
            @click="isExpanded = true"
        >
            <Maximize2 class="h-4 w-4" />
            <span class="sr-only">Expand</span>
        </Button>

        <div ref="inlineContainer" class="h-full w-full" />
    </div>

    <Teleport to="body">
        <div
            v-if="isExpanded"
            ref="expandedWrapper"
            tabindex="-1"
            class="fixed inset-0 z-[9999] h-[100dvh] w-[100dvw] overflow-hidden bg-background outline-none"
            :class="{ 'excalidraw-editor--hide-ui': props.hideUi }"
            @keydown.esc.stop.prevent="isExpanded = false"
        >
            <Button
                type="button"
                variant="secondary"
                size="sm"
                class="absolute top-3 right-3 z-[10000] shadow-sm"
                aria-label="Collapse drawing editor"
                @click="isExpanded = false"
            >
                <Minimize2 class="h-4 w-4" />
                <span class="sr-only">Collapse</span>
            </Button>

            <div ref="expandedContainer" class="h-full w-full" />
        </div>
    </Teleport>
</template>

<style scoped>
.excalidraw-editor--hide-ui :deep(.App-bottom-bar),
.excalidraw-editor--hide-ui :deep(.FixedSideContainer),
.excalidraw-editor--hide-ui :deep(.layer-ui__wrapper) {
    display: none !important;
}
</style>
