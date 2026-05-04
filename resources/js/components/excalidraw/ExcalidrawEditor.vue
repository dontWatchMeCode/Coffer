<script setup lang="ts">
import { Maximize2, Minimize2 } from 'lucide-vue-next';
import { createRoot } from 'react-dom/client';
import type { Root } from 'react-dom/client';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { ExcalidrawCanvas } from '@/components/excalidraw/ExcalidrawCanvas';
import { Button } from '@/components/ui/button';
import type { ExcalidrawScene } from '@/types';

const props = withDefaults(
    defineProps<{
        modelValue?: ExcalidrawScene | null;
        readonly?: boolean;
        name?: string;
    }>(),
    {
        modelValue: null,
        readonly: false,
        name: 'Drawing',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: ExcalidrawScene];
}>();

const isExpanded = ref(false);
const inlineContainer = ref<HTMLDivElement | null>(null);
const expandedContainer = ref<HTMLDivElement | null>(null);
let root: Root | null = null;
let rootElement: HTMLDivElement | null = null;
let canvasComponent: typeof ExcalidrawCanvas | null = null;

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
        canvasComponent({
            initialData: props.modelValue,
            name: props.name,
            readonly: props.readonly,
            onChange: (scene) => emit('update:modelValue', scene),
        }),
    );
}

function handleKeydown(event: KeyboardEvent): void {
    if (!isExpanded.value || event.key !== 'Escape') {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    isExpanded.value = false;
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown, { capture: true });
    void renderCanvas();
});

watch(
    () => [props.readonly, props.name],
    () => void renderCanvas(),
);

watch(isExpanded, async (expanded) => {
    document.body.style.overflow = expanded ? 'hidden' : '';

    await nextTick();
    void renderCanvas();
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown, { capture: true });
    document.body.style.overflow = '';
    root?.unmount();
    root = null;
    rootElement = null;
});
</script>

<template>
    <div
        v-show="!isExpanded"
        class="relative h-[620px] min-h-[420px] overflow-hidden rounded-lg border bg-background"
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
            class="fixed inset-0 z-[9999] h-[100dvh] w-[100dvw] overflow-hidden bg-background"
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
