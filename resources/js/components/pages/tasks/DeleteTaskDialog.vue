<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

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
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Delete task?</DialogTitle>
                <DialogDescription>
                    This will permanently remove this task and its comments.
                </DialogDescription>
            </DialogHeader>

            <div
                class="rounded-lg border bg-muted/40 p-3 text-sm leading-6 text-muted-foreground"
            >
                {{ taskTitle }}
            </div>

            <DialogFooter class="gap-2">
                <Button
                    type="button"
                    variant="secondary"
                    @click="emit('update:open', false)"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    class="gap-2"
                    @click="emit('confirm')"
                >
                    <Trash2 class="h-4 w-4" />
                    Delete task
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
