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
            <span v-if="change.added" class="bg-success/10 px-0.5 text-success">
                {{ change.value }}
            </span>
            <span
                v-else-if="change.removed"
                class="bg-destructive/10 px-0.5 text-destructive line-through"
            >
                {{ change.value }}
            </span>
            <span v-else>{{ change.value }}</span>
        </template>
    </span>
</template>
