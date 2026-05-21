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
        title="Move task to trash?"
        description="This moves the task to trash. You can restore it from project trash."
        confirm-label="Move to trash"
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
