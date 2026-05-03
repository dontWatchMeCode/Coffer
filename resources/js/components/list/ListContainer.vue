<script setup lang="ts">
type Layout = 'list' | 'grid';

type Props = {
    layout?: Layout;
};

const props = withDefaults(defineProps<Props>(), {
    layout: 'list',
});

const layoutClasses: Record<Layout, string> = {
    list: 'space-y-3',
    grid: 'grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3',
};
</script>

<template>
    <div :class="layoutClasses[props.layout]">
        <slot />
    </div>
</template>
