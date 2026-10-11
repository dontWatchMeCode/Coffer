<script setup lang="ts">
import type { Component } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    value: string | number;
    detail?: string;
    icon?: Component;
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <Card :class="cn('gap-0 py-0', props.class)">
        <CardHeader>
            <CardTitle class="flex items-center justify-between gap-3">
                <span>{{ label }}</span>
                <component
                    v-if="icon"
                    :is="icon"
                    class="size-3.5 text-muted-foreground"
                />
            </CardTitle>
        </CardHeader>
        <div class="min-w-0 px-3 py-3.5">
            <p
                class="text-[2.375rem] leading-tight font-bold tracking-[-1px] wrap-anywhere text-heading tabular-nums"
            >
                {{ value }}
            </p>
            <p v-if="detail" class="mt-1.5 text-xs text-muted-foreground">
                {{ detail }}
            </p>
        </div>
    </Card>
</template>
