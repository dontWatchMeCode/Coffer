<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import { dashboard as teamDashboard } from '@/routes/team';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const page = usePage();
const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? teamDashboard(page.props.currentTeam.slug).url
        : dashboard().url,
);
defineOptions({ inheritAttrs: false });
</script>

<template>
    <div class="mx-auto flex min-h-svh max-w-[1400px] flex-col px-4 pb-10">
        <Head title="Welcome" />
        <header
            class="flex flex-wrap items-center justify-between gap-4 border-b py-3"
        >
            <span class="text-sm font-semibold text-primary">Workspace</span>
            <nav aria-label="Account" class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="dashboardUrl">Dashboard</Link>
                </Button>
                <template v-else>
                    <Button variant="outline" as-child>
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <Button v-if="canRegister" as-child>
                        <Link :href="register()">Register</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main class="flex flex-1 items-center justify-center py-10">
            <section class="w-full max-w-2xl border bg-card">
                <div
                    class="flex items-center justify-between border-b bg-secondary px-4 py-2 text-xs"
                >
                    <span class="font-semibold text-primary"
                        >Workspace / Welcome</span
                    >
                    <span class="text-muted-foreground">Getting started</span>
                </div>
                <div class="space-y-6 p-5 sm:p-8">
                    <div class="space-y-2">
                        <h1 class="text-lg font-semibold">Let's get started</h1>
                        <p class="text-sm text-muted-foreground">
                            Laravel has an incredibly rich ecosystem. We suggest
                            starting with the following.
                        </p>
                    </div>
                    <ul class="divide-y divide-dashed border-y border-dashed">
                        <li
                            class="flex flex-wrap items-center justify-between gap-2 py-3"
                        >
                            <span class="text-muted-foreground"
                                >01 / Read the documentation</span
                            >
                            <a
                                href="https://laravel.com/docs"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary underline underline-offset-4"
                                >Documentation ↗</a
                            >
                        </li>
                        <li
                            class="flex flex-wrap items-center justify-between gap-2 py-3"
                        >
                            <span class="text-muted-foreground"
                                >02 / Watch video tutorials</span
                            >
                            <a
                                href="https://laracasts.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary underline underline-offset-4"
                                >Laracasts ↗</a
                            >
                        </li>
                    </ul>
                    <Button variant="outline" as-child>
                        <a
                            href="https://cloud.laravel.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            >Deploy now ↗</a
                        >
                    </Button>
                </div>
            </section>
        </main>
    </div>
</template>
