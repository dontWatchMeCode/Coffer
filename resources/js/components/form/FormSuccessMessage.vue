<script setup lang="ts">
type Props = {
    show: boolean;
    message?: string;
};

withDefaults(defineProps<Props>(), {
    message: 'Saved.',
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-in-out"
        enter-from-class="opacity-0"
        leave-active-class="transition ease-in-out"
        leave-to-class="opacity-0"
    >
        <p v-show="show" class="text-sm text-neutral-600">
            {{ message }}
        </p>
    </Transition>
</template>
