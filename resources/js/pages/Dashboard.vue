<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmailVerifiedDialog from '@/components/auth/EmailVerifiedDialog.vue';
import RichTextEditor from '@/components/richtext/RichTextEditor.vue';
import { dashboard } from '@/routes';
import { dashboard as teamDashboard } from '@/routes/team';
import type { Team } from '@/types';

const editorContent = ref(`# Task brief

This editor now runs natively in Vue with markdown as the source of truth.

- Typing updates Vue state through v-model.
- The saved format stays readable for humans and agents.`);

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

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
        <div
            class="relative flex-1 overflow-hidden rounded-3xl border border-sidebar-border/70 bg-gradient-to-br from-background via-background to-muted/40 md:min-h-min dark:border-sidebar-border"
        >
            <div
                class="grid h-full gap-6 p-4 xl:grid-cols-[minmax(0,1.7fr)_minmax(20rem,0.8fr)]"
            >
                <section class="flex min-h-[32rem] flex-col gap-4">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div class="space-y-2">
                            <p
                                class="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase"
                            >
                                Rich text editor
                            </p>
                            <h2 class="text-2xl font-semibold tracking-tight">
                                Quiet Focus editor
                            </h2>
                            <p class="max-w-2xl text-sm text-muted-foreground">
                                Minimal chrome, readable spacing, and the new
                                default editor style everywhere.
                            </p>
                        </div>
                    </div>

                    <RichTextEditor
                        v-model="editorContent"
                        class="flex-1"
                        placeholder="Write the task brief..."
                    />
                </section>

                <section class="flex min-h-[32rem] flex-col gap-4">
                    <div class="space-y-1">
                        <h2 class="text-lg font-semibold">Stored Markdown</h2>
                        <p class="text-sm text-muted-foreground">
                            Persist this source directly instead of JSON.
                        </p>
                    </div>

                    <pre
                        class="min-h-0 flex-1 overflow-auto rounded-xl border bg-muted/40 p-4 text-xs leading-6"
                    ><code>{{ editorContent }}</code></pre>
                </section>
            </div>
        </div>
    </div>
</template>
