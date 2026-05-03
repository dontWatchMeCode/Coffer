<script setup lang="ts">
type Props = {
    clickable?: boolean;
    ariaLabel?: string;
};

const props = withDefaults(defineProps<Props>(), {
    clickable: true,
});

const emit = defineEmits<{
    (e: 'click'): void;
}>();

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        emit('click');
    }
}
</script>

<template>
    <div
        class="group rounded-lg border bg-card p-3 transition-colors hover:bg-accent/50 dark:bg-card/50"
        :class="{ 'cursor-pointer': props.clickable }"
        :role="props.clickable ? 'button' : undefined"
        :tabindex="props.clickable ? 0 : undefined"
        :aria-label="props.ariaLabel || undefined"
        @click="props.clickable && emit('click')"
        @keydown="props.clickable && handleKeydown"
    >
        <slot />
    </div>
</template>
