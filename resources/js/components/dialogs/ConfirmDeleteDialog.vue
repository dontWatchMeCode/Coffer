<script setup lang="ts">
import type { Component } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Props = {
    open: boolean;
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    cancelVariant?:
        | 'default'
        | 'destructive'
        | 'outline'
        | 'secondary'
        | 'ghost'
        | 'link';
    confirmDataTestid?: string;
    confirmIcon?: Component;
    processing?: boolean;
};

withDefaults(defineProps<Props>(), {
    description: '',
    confirmLabel: 'Delete',
    cancelLabel: 'Cancel',
    cancelVariant: 'outline',
    confirmDataTestid: undefined,
    confirmIcon: undefined,
    processing: false,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <slot name="description">
                    <DialogDescription v-if="description">
                        {{ description }}
                    </DialogDescription>
                </slot>
            </DialogHeader>

            <slot />

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button
                        type="button"
                        :variant="cancelVariant"
                        class="cursor-pointer"
                    >
                        {{ cancelLabel }}
                    </Button>
                </DialogClose>
                <Button
                    type="button"
                    variant="destructive"
                    class="cursor-pointer"
                    :data-testid="confirmDataTestid"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    <component
                        :is="confirmIcon"
                        v-if="confirmIcon"
                        class="mr-1.5 h-3.5 w-3.5"
                    />
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
