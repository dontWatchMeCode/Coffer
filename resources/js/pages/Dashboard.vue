<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { defineAsyncComponent, ref } from 'vue';
import type { BlockNoteDocument } from '@/components/blocknote/document';
import EmailVerifiedDialog from '@/components/EmailVerifiedDialog.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { dashboard } from '@/routes';
import { dashboard as teamDashboard } from '@/routes/team';
import type { Team } from '@/types';

const BlockNoteEditor = defineAsyncComponent(
    () => import('@/components/BlockNoteEditor.vue'),
);

const editorContent = ref<BlockNoteDocument>([
    {
        type: 'heading',
        content: 'BlockNote task brief',
        props: { level: 2 },
    },
    {
        type: 'paragraph',
        content: 'This editor runs as a React island inside the Vue dashboard.',
    },
    {
        type: 'bulletListItem',
        content: 'Typing updates Vue state through v-model.',
    },
    {
        type: 'bulletListItem',
        content: 'The saved source of truth is BlockNote JSON.',
    },
]);

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam
                    ? teamDashboard(props.currentTeam.slug).url
                    : dashboard().url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Dashboard" />

    <EmailVerifiedDialog />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <div
                class="grid h-full gap-6 p-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(20rem,1fr)]"
            >
                <section class="flex min-h-[32rem] flex-col gap-4">
                    <div class="space-y-1">
                        <h2 class="text-lg font-semibold">BlockNote Demo</h2>
                        <p class="text-sm text-muted-foreground">
                            Vue owns the page state. React only renders the
                            editor surface.
                        </p>
                    </div>

                    <BlockNoteEditor
                        v-model="editorContent"
                        class="flex-1"
                        placeholder="Write the task brief..."
                    />
                </section>

                <section class="flex min-h-[32rem] flex-col gap-4">
                    <div class="space-y-1">
                        <h2 class="text-lg font-semibold">Stored JSON</h2>
                        <p class="text-sm text-muted-foreground">
                            Persist this structure instead of HTML.
                        </p>
                    </div>

                    <pre
                        class="min-h-0 flex-1 overflow-auto rounded-xl border bg-muted/40 p-4 text-xs leading-6"
                    ><code>{{ JSON.stringify(editorContent, null, 2) }}</code></pre>
                </section>
            </div>
        </div>
    </div>
</template>
