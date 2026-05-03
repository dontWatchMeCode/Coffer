<script setup lang="ts">
import { Button } from '@/components/ui/button';

type Props = {
    title: string;
    description?: string;
    actionLabel?: string;
    showAction?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    description: '',
    actionLabel: '',
    showAction: false,
});

const emit = defineEmits<{
    (e: 'action'): void;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center"
    >
        <div class="mb-3 text-muted-foreground/50">
            <slot name="icon" />
        </div>
        <p class="font-medium">{{ props.title }}</p>
        <p
            v-if="props.description"
            class="mt-1 max-w-sm text-sm text-muted-foreground"
        >
            {{ props.description }}
        </p>
        <Button
            v-if="props.showAction"
            variant="outline"
            size="sm"
            class="mt-4 cursor-pointer"
            @click="emit('action')"
        >
            <slot name="action-icon" />
            {{ props.actionLabel }}
        </Button>
    </div>
</template>
