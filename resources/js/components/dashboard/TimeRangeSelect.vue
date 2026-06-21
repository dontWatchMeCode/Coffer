<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type RangeOption = { value: string; label: string };

type Props = {
    modelValue?: string;
    options: RangeOption[];
};

const props = defineProps<Props>();

function onChange(value: AcceptableValue): void {
    if (typeof value !== 'string') {
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('range', value);
    router.visit(url.pathname + url.search, {
        preserveScroll: true,
        preserveState: true,
        only: ['insights', 'range'],
    });
}
</script>

<template>
    <Select :model-value="props.modelValue" @update:model-value="onChange">
        <SelectTrigger size="sm" class="w-[150px]">
            <SelectValue placeholder="Select range" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="option in props.options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
