<script setup lang="ts">
import type { Component } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Card } from '@/components/ui/card';
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
        <div class="flex items-start justify-between gap-3 p-5">
            <div class="min-w-0 space-y-1">
                <p class="text-xs font-medium text-muted-foreground">
                    {{ label }}
                </p>
                <p class="text-2xl leading-none font-semibold tabular-nums">
                    {{ value }}
                </p>
                <p v-if="detail" class="text-xs text-muted-foreground">
                    {{ detail }}
                </p>
            </div>
            <div
                v-if="icon"
                class="rounded-lg bg-muted p-2 text-muted-foreground"
            >
                <component :is="icon" class="h-4 w-4" />
            </div>
        </div>
    </Card>
</template>
