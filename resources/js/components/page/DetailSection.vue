<script setup lang="ts">
type Props = {
    title: string;
    empty?: string;
    hasContent?: boolean;
};

withDefaults(defineProps<Props>(), {
    empty: '',
    hasContent: true,
});
</script>

<template>
    <section class="space-y-2">
        <h2 class="text-sm font-medium text-muted-foreground">
            {{ title }}
        </h2>

        <slot v-if="hasContent" />

        <p v-else class="text-sm text-muted-foreground italic">
            {{ empty }}
        </p>
    </section>
</template>
