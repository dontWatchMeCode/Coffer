<script setup lang="ts">
import ExcalidrawEditor from '@/components/excalidraw/ExcalidrawEditor.vue';
import type { ExcalidrawPayload } from '@/types/notes';

withDefaults(
    defineProps<{
        payload?: ExcalidrawPayload | null;
        editable?: boolean;
        name?: string;
    }>(),
    {
        payload: null,
        editable: true,
        name: 'Drawing',
    },
);

const emit = defineEmits<{
    'update:payload': [value: ExcalidrawPayload];
}>();

function handleUpdate(scene: unknown): void {
    emit('update:payload', { scene: scene as ExcalidrawPayload['scene'] });
}
</script>

<template>
    <ExcalidrawEditor
        :model-value="payload?.scene ?? null"
        :readonly="!editable"
        :name="name"
        height="400px"
        @update:model-value="handleUpdate"
    />
</template>
