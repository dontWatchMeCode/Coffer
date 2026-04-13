<script setup lang="ts">
import { createElement } from 'react';
import type { Root } from 'react-dom/client';
import { createRoot } from 'react-dom/client';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    serializeBlockNoteDocument,
    type BlockNoteDocument,
} from '@/components/blocknote/document';
import ReactBlockNoteHost from '@/components/blocknote/ReactBlockNoteHost';

const props = withDefaults(
    defineProps<{
        editable?: boolean;
        initialContent?: BlockNoteDocument | null;
        modelValue?: BlockNoteDocument | null;
        placeholder?: string;
    }>(),
    {
        editable: true,
        initialContent: undefined,
        modelValue: undefined,
        placeholder: undefined,
    },
);

const emit = defineEmits<{
    blur: [];
    change: [value: BlockNoteDocument];
    focus: [];
    ready: [];
    'update:modelValue': [value: BlockNoteDocument];
}>();

const mountEl = ref<HTMLDivElement | null>(null);
const content = computed<BlockNoteDocument | null | undefined>(() => {
    return props.modelValue ?? props.initialContent;
});

let reactRoot: Root | null = null;
let lastEmittedDocument = '';

function renderReactHost(): void {
    if (!reactRoot) {
        return;
    }

    reactRoot.render(
        createElement(ReactBlockNoteHost, {
            content: content.value,
            editable: props.editable,
            onBlur: () => emit('blur'),
            onChange: (value: BlockNoteDocument) => {
                lastEmittedDocument = serializeBlockNoteDocument(value);
                emit('update:modelValue', value);
                emit('change', value);
            },
            onFocus: () => emit('focus'),
            onReady: () => emit('ready'),
            placeholder: props.placeholder,
        }),
    );
}

onMounted(() => {
    if (!mountEl.value) {
        return;
    }

    reactRoot = createRoot(mountEl.value);
    renderReactHost();
});

watch(content, () => {
    const serializedContent = serializeBlockNoteDocument(content.value);

    if (serializedContent === lastEmittedDocument) {
        return;
    }

    renderReactHost();
    lastEmittedDocument = serializedContent;
});

watch([() => props.editable, () => props.placeholder], () => {
    renderReactHost();
});

onBeforeUnmount(() => {
    reactRoot?.unmount();
    reactRoot = null;
});
</script>

<template>
    <div class="w-full rounded-xl border bg-card p-3 shadow-sm">
        <div ref="mountEl" class="blocknote-editor min-h-64 w-full" />
    </div>
</template>
