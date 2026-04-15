<script setup lang="ts">
import {
    defineAsyncComponent,
    computed,
    nextTick,
    onMounted,
    ref,
    watch,
} from 'vue';
import type { BlockNoteDocument } from '@/components/blocknote/document';
import {
    isSerializedBlockNoteBody,
    parseBlockNoteBody,
    serializeBlockNoteDocument,
} from '@/components/blocknote/document';
import { renderBlockNoteDocumentAsHtml } from '@/components/blocknote/html';

const BlockNoteEditor = defineAsyncComponent(
    () => import('@/components/BlockNoteEditor.vue'),
);

const props = withDefaults(
    defineProps<{
        editable?: boolean;
        modelValue?: string | null;
        placeholder?: string;
    }>(),
    {
        editable: true,
        modelValue: null,
        placeholder: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const rootEl = ref<HTMLDivElement | null>(null);
const isReady = ref(!props.editable);
const shouldScrollIntoView = ref(false);

const document = computed<BlockNoteDocument | null>(() =>
    parseBlockNoteBody(props.modelValue),
);
const isStructuredDocument = computed<boolean>(() =>
    isSerializedBlockNoteBody(props.modelValue),
);
const readonlyHtml = computed<string>(() => {
    if (!isStructuredDocument.value) {
        return '';
    }

    return renderBlockNoteDocumentAsHtml(document.value);
});

const lastSerialized = ref<string>(props.modelValue ?? '');

onMounted(() => {
    if (!props.editable) {
        return;
    }

    shouldScrollIntoView.value = true;
});

watch(
    () => props.editable,
    async (editable, wasEditable) => {
        isReady.value = !editable;

        if (!editable || wasEditable) {
            return;
        }

        shouldScrollIntoView.value = true;
        await nextTick();
    },
);

watch(
    () => props.modelValue,
    (value) => {
        lastSerialized.value = value ?? '';
    },
);

function handleChange(doc: BlockNoteDocument): void {
    const serialized = serializeBlockNoteDocument(doc);

    if (serialized === lastSerialized.value) {
        return;
    }

    lastSerialized.value = serialized;
    emit('update:modelValue', serialized);
}

function handleReady(): void {
    isReady.value = true;

    if (!shouldScrollIntoView.value) {
        return;
    }

    shouldScrollIntoView.value = false;
    rootEl.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
        inline: 'nearest',
    });
}
</script>

<template>
    <div
        ref="rootEl"
        :class="[
            'blocknote-comment-editor w-full',
            isReady ? '' : 'opacity-0',
            !editable ? 'blocknote-comment-editor--readonly' : '',
        ]"
    >
        <BlockNoteEditor
            v-if="editable"
            :editable="editable"
            :model-value="document"
            :placeholder="placeholder"
            @change="handleChange"
            @ready="handleReady"
        />
        <div
            v-else
            class="blocknote-comment-editor__html prose prose-sm max-w-none"
        >
            <div v-if="isStructuredDocument" v-html="readonlyHtml" />
            <p v-else class="whitespace-pre-wrap">{{ modelValue ?? '' }}</p>
        </div>
    </div>
</template>

<style>
.blocknote-comment-editor > div {
    border: none;
    background: transparent;
    box-shadow: none;
    padding: 0;
    border-radius: 0;
}

.blocknote-comment-editor .blocknote-editor {
    min-height: 0;
}

.blocknote-comment-editor .ProseMirror {
    padding-top: 28px;
}

.blocknote-comment-editor--readonly .blocknote-comment-editor__html {
    border-radius: 0.5rem;
    background: hsl(var(--muted));
}

.blocknote-comment-editor__html > :first-child {
    margin-top: 0;
}

.blocknote-comment-editor__html > :last-child {
    margin-bottom: 0;
}

.blocknote-comment-editor__html p {
    min-height: 24px;
}
</style>
