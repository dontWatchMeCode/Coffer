<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';

defineProps<{
    open: boolean;
    kind: 'row' | 'column';
    label: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <ConfirmDeleteDialog
        :open="open"
        :title="kind === 'column' ? 'Delete column' : 'Delete row'"
        :description="`Delete ${label}? This will be applied when you save the spreadsheet.`"
        confirm-label="Delete"
        confirm-data-testid="confirm-spreadsheet-grid-delete"
        :confirm-icon="Trash2"
        @update:open="emit('update:open', $event)"
        @confirm="emit('confirm')"
    />
</template>
