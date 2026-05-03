<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';

type Props = {
    href: string;
    value: string;
    label?: string;
    external?: boolean;
};

withDefaults(defineProps<Props>(), {
    label: '',
    external: false,
});
</script>

<template>
    <a
        :href="href"
        :target="external ? '_blank' : undefined"
        :rel="external ? 'noopener noreferrer' : undefined"
        class="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm hover:bg-accent/50"
    >
        <span class="flex min-w-0 items-center gap-2">
            <slot name="icon" />
            <span class="truncate">{{ value }}</span>
        </span>

        <span
            v-if="label || external"
            class="flex shrink-0 items-center gap-2 text-xs text-muted-foreground"
        >
            <span v-if="label">{{ label }}</span>
            <ExternalLink v-if="external" class="h-3.5 w-3.5" />
        </span>
    </a>
</template>
