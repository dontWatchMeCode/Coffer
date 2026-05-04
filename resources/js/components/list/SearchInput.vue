<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';

type Props = {
    modelValue: string;
    placeholder?: string;
    dataTestid?: string;
};

withDefaults(defineProps<Props>(), {
    placeholder: 'Search...',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <div class="relative w-full max-w-sm">
        <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            :model-value="modelValue"
            :placeholder="placeholder"
            :data-testid="dataTestid"
            class="pl-9"
            @update:model-value="emit('update:modelValue', String($event))"
        />
    </div>
</template>
