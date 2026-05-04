<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type Props = {
    open: boolean;
    title: string;
    description: string;
    submitLabel?: string;
    submitDisabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    submitLabel: 'Create',
    submitDisabled: false,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    submit: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>

        <DialogContent class="max-h-[85vh] overflow-y-auto">
            <form class="space-y-4" @submit.prevent="emit('submit')">
                <DialogHeader>
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription>
                        {{ description }}
                    </DialogDescription>
                </DialogHeader>

                <slot />

                <div class="flex justify-end">
                    <Button type="submit" :disabled="submitDisabled">
                        {{ submitLabel }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
