<script setup lang="ts">
import type { ViewMode } from '@/composables/useViewMode';

type Props = {
    layout?: ViewMode;
};

const props = withDefaults(defineProps<Props>(), {
    layout: 'grid',
});

const layoutClasses: Record<ViewMode, string> = {
    list: 'space-y-3',
    grid: 'grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3',
};
</script>

<template>
    <div :class="layoutClasses[props.layout]">
        <slot />
    </div>
</template>
