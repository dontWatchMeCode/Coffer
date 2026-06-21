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
        <div class="border-b">
            <CardHeader class="px-4 py-4">
                <CardTitle
                    class="flex items-center justify-between gap-3 text-base"
                >
                    <span class="flex flex-col gap-1">
                        <span class="text-base font-semibold tracking-tight">
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
        </div>
        <CardContent class="p-4">
            <slot />
        </CardContent>
    </Card>
</template>
