<script setup lang="ts">
import AppContent from '@/components/app/AppContent.vue';
import AppShell from '@/components/app/AppShell.vue';
import AppSidebar from '@/components/app/AppSidebar.vue';
import AppSidebarHeader from '@/components/app/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebarHeader :breadcrumbs="breadcrumbs" />
        <div
            data-testid="workspace-columns"
            class="flex min-w-0 flex-1 flex-col items-stretch gap-[26px] pt-5 min-[1081px]:flex-row"
        >
            <AppSidebar />
            <AppContent variant="sidebar" class="overflow-x-clip">
                <slot />
            </AppContent>
        </div>
    </AppShell>
</template>
