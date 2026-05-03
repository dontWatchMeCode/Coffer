<script setup lang="ts">
type Props = {
    size?: 'sm' | 'md';
    rounded?: 'md' | 'lg';
};

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    rounded: 'md',
});

const sizeClasses = {
    sm: 'h-9 w-9',
    md: 'h-10 w-10',
};

const roundedClasses = {
    md: 'rounded-md',
    lg: 'rounded-lg',
};
</script>

<template>
    <div
        class="flex shrink-0 items-center justify-center bg-muted"
        :class="[sizeClasses[props.size], roundedClasses[props.rounded]]"
    >
        <slot />
    </div>
</template>
