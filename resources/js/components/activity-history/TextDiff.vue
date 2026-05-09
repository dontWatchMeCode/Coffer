<script setup lang="ts">
import { diffWords } from 'diff';
import { computed } from 'vue';

const props = defineProps<{
    oldText: string;
    newText: string;
}>();

const changes = computed(() => diffWords(props.oldText, props.newText));
</script>

<template>
    <span class="text-xs leading-relaxed whitespace-pre-wrap">
        <template v-for="(change, idx) in changes" :key="idx">
            <span
                v-if="change.added"
                class="rounded bg-green-100 px-0.5 text-green-800 dark:bg-green-900/30 dark:text-green-400"
            >
                {{ change.value }}
            </span>
            <span
                v-else-if="change.removed"
                class="rounded bg-red-100 px-0.5 text-red-800 line-through dark:bg-red-900/30 dark:text-red-400"
            >
                {{ change.value }}
            </span>
            <span v-else>{{ change.value }}</span>
        </template>
    </span>
</template>
