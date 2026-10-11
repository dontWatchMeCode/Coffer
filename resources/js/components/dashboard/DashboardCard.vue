<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import TimeRangeSelect from '@/components/dashboard/TimeRangeSelect.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type RangeOption = { value: string; label: string };

type Props = {
    title: string;
    description?: string;
    range?: string;
    rangeOptions?: RangeOption[];
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <Card :class="cn('gap-0 py-0', props.class)">
        <CardHeader>
            <CardTitle class="flex items-center justify-between gap-3 text-xs">
                <span class="flex flex-col gap-1">
                    <span class="text-xs font-semibold">
                        {{ title }}
                    </span>
                    <span
                        v-if="description"
                        class="text-xs font-normal text-muted-foreground"
                    >
                        {{ description }}
                    </span>
                </span>
                <TimeRangeSelect
                    v-if="rangeOptions?.length"
                    :model-value="range"
                    :options="rangeOptions"
                />
            </CardTitle>
        </CardHeader>
        <CardContent class="p-4">
            <slot />
        </CardContent>
    </Card>
</template>
