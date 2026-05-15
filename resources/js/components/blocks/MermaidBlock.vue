<script lang="ts">
let globalRenderCount = 0;
</script>

<script setup lang="ts">
import mermaid from 'mermaid';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import type { MermaidPayload } from '@/types/notes';

const instanceId = `mermaid-inst-${++globalRenderCount}`;

const props = withDefaults(
    defineProps<{
        payload?: MermaidPayload | null;
        editable?: boolean;
    }>(),
    {
        payload: null,
        editable: true,
    },
);

const emit = defineEmits<{
    'update:payload': [value: MermaidPayload];
}>();

const renderedSvg = ref('');
const renderError = ref('');
let renderGeneration = 0;

function mermaidTheme(): 'default' | 'dark' {
    return document.documentElement.classList.contains('dark')
        ? 'dark'
        : 'default';
}

let currentTheme: 'default' | 'dark' = mermaidTheme();

mermaid.initialize({
    startOnLoad: false,
    theme: currentTheme,
    securityLevel: 'strict',
});

async function renderDiagram(source: string): Promise<void> {
    if (!source.trim()) {
        renderedSvg.value = '';
        renderError.value = '';

        return;
    }

    const generation = ++renderGeneration;

    try {
        const id = `${instanceId}-${++globalRenderCount}`;
        const { svg } = await mermaid.render(id, source);

        if (generation !== renderGeneration) {
            return;
        }

        renderedSvg.value = svg;
        renderError.value = '';
    } catch {
        if (generation !== renderGeneration) {
            return;
        }

        renderError.value = 'Invalid diagram syntax';
        renderedSvg.value = '';
    }
}

function handleInput(event: Event): void {
    const value = (event.target as HTMLTextAreaElement).value;
    emit('update:payload', { content: value });
}

let observer: MutationObserver | null = null;

onMounted(() => {
    if (props.payload?.content) {
        renderDiagram(props.payload.content);
    }

    observer = new MutationObserver(() => {
        const newTheme = mermaidTheme();

        if (newTheme !== currentTheme && props.payload?.content) {
            currentTheme = newTheme;
            mermaid.initialize({
                startOnLoad: false,
                theme: newTheme,
                securityLevel: 'strict',
            });
            renderDiagram(props.payload.content);
        }
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});

onUnmounted(() => {
    renderGeneration++;
    observer?.disconnect();
});

watch(
    () => props.payload?.content,
    (content) => {
        renderDiagram(content ?? '');
    },
);
</script>

<template>
    <div>
        <textarea
            v-if="editable"
            :value="payload?.content ?? ''"
            class="w-full rounded-md border bg-background px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-ring focus:outline-none"
            rows="8"
            placeholder="graph TD&#10;    A[Start] --> B{Decision}&#10;    B -->|Yes| C[Action]&#10;    B -->|No| D[End]"
            @input="handleInput"
        />

        <div
            v-if="editable && renderError"
            class="mt-1 text-xs text-destructive"
        >
            {{ renderError }}
        </div>

        <div
            v-if="renderedSvg"
            class="overflow-x-auto rounded-md border bg-background p-4"
            :class="editable ? 'mt-2' : ''"
            v-html="renderedSvg"
        />

        <div
            v-else-if="!editable && !payload?.content"
            class="py-4 text-center text-sm text-muted-foreground italic"
        >
            Empty diagram
        </div>
    </div>
</template>
