<script setup lang="ts">
import RichTextEditor from '@/components/richtext/RichTextEditor.vue';
import type { TextPayload } from '@/types/notes';

withDefaults(
    defineProps<{
        payload?: TextPayload | null;
        editable?: boolean;
        placeholder?: string;
    }>(),
    {
        payload: null,
        editable: true,
        placeholder: 'Write something...',
    },
);

const emit = defineEmits<{
    'update:payload': [value: TextPayload];
}>();

function handleInput(value: string): void {
    emit('update:payload', { content: value });
}
</script>

<template>
    <RichTextEditor
        :model-value="payload?.content ?? ''"
        :editable="editable"
        :placeholder="placeholder"
        @update:model-value="handleInput"
    />
</template>
