<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M38 10H10v44h44V26"
            fill="none"
            stroke="currentColor"
            stroke-width="6"
        />
        <path d="M21 23h12v12H21z" fill="currentColor" />
        <path
            d="M21 44h22M44 10h10v10H44z"
            fill="none"
            stroke="currentColor"
            stroke-width="4"
        />
    </svg>
</template>
