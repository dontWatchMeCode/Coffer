<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';

type Props = {
    open: boolean;
    taskTitle: string;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <ConfirmDeleteDialog
        :open="open"
        title="Delete task?"
        description="This will permanently remove this task and its comments."
        confirm-label="Delete task"
        :confirm-icon="Trash2"
        @update:open="emit('update:open', $event)"
        @confirm="emit('confirm')"
    >
        <div
            class="rounded-lg border bg-muted/40 p-3 text-sm leading-6 text-muted-foreground"
        >
            {{ taskTitle }}
        </div>
    </ConfirmDeleteDialog>
</template>
