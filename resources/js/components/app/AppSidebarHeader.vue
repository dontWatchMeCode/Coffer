<script setup lang="ts">
import Breadcrumbs from '@/components/nav/Breadcrumbs.vue';
import TeamSwitcher from '@/components/nav/TeamSwitcher.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-[60px] shrink-0 items-center gap-3.5 border-b bg-background"
    >
        <SidebarTrigger class="min-[1081px]:hidden" />
        <TeamSwitcher />
        <div class="ml-auto flex min-w-0 items-center gap-2">
            <div
                v-if="breadcrumbs && breadcrumbs.length > 0"
                class="min-w-0 overflow-x-auto"
                tabindex="0"
                role="region"
                aria-label="Breadcrumbs"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </header>
</template>
