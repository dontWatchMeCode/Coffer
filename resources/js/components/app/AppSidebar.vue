<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { LayoutGrid, Search } from 'lucide-vue-next';
import { computed, onUnmounted, ref, useTemplateRef, watch } from 'vue';
import NavMain from '@/components/nav/NavMain.vue';
import NavUser from '@/components/nav/NavUser.vue';
import SearchOverlay from '@/components/nav/SearchOverlay.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { TEAM_NAVIGATION_ITEMS } from '@/lib/app-navigation';
import { dashboard } from '@/routes';
import { dashboard as teamDashboard } from '@/routes/team';
import type { NavItem, TeamFeatureKey } from '@/types';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? teamDashboard(page.props.currentTeam.slug).url
        : dashboard().url,
);

function featureEnabled(feature: TeamFeatureKey): boolean {
    return page.props.currentTeam?.featureSettings?.[feature] ?? true;
}

const mainNavItems = computed<NavItem[]>(() => {
    const currentTeam = page.props.currentTeam;

    return [
        {
            title: 'Dashboard',
            href: dashboardUrl.value,
            icon: LayoutGrid,
            isActive: page.component === 'Dashboard',
        },
        ...(currentTeam
            ? TEAM_NAVIGATION_ITEMS.filter((item) =>
                  featureEnabled(item.feature),
              ).map((item) => ({
                  title: item.title,
                  href: item.href(currentTeam.slug),
                  icon: item.icon,
              }))
            : []),
    ];
});

const searchOpen = ref(false);
const { openMobile, setOpenMobile, isMobile } = useSidebar();
const navRef = useTemplateRef<HTMLDivElement>('navRef');

let lastFocused: Element | null = null;
let didLockBody = false;

// Keep the drawer closed across navigations and restore body scroll.
watch(
    () => page.url,
    () => {
        setOpenMobile(false);
    },
);

watch(openMobile, (open) => {
    if (typeof document === 'undefined') {
        return;
    }

    if (open && isMobile.value) {
        document.body.style.overflow = 'hidden';
        didLockBody = true;
        lastFocused = document.activeElement;
        requestAnimationFrame(() => {
            navRef.value
                ?.querySelector<HTMLElement>('a[href], button:not([disabled])')
                ?.focus();
        });

        return;
    }

    if (didLockBody) {
        document.body.style.overflow = '';
        didLockBody = false;
    }

    if (
        lastFocused instanceof HTMLElement &&
        navRef.value?.contains(document.activeElement)
    ) {
        lastFocused.focus();
    }

    lastFocused = null;
});

watch(isMobile, (mobile) => {
    if (!mobile) {
        setOpenMobile(false);
    }
});

useEventListener('keydown', (event: KeyboardEvent) => {
    if (!openMobile.value) {
        return;
    }

    const target = event.target as HTMLElement | null;

    // Let Radix dialogs above the drawer handle their own keys. (The
    // drawer panel itself carries role="dialog" when open.)
    if (target?.closest('[data-slot="dialog-content"]')) {
        return;
    }

    if (event.key === 'Escape') {
        setOpenMobile(false);

        return;
    }

    if (event.key === 'Tab' && isMobile.value && navRef.value) {
        const targets = navRef.value.querySelectorAll<HTMLElement>(
            'a[href], button:not([disabled])',
        );

        if (!targets.length) {
            return;
        }

        const first = targets[0];
        const last = targets[targets.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
});

onUnmounted(() => {
    if (typeof document !== 'undefined' && didLockBody) {
        document.body.style.overflow = '';
        didLockBody = false;
    }
});
</script>

<template>
    <div
        ref="navRef"
        data-testid="mobile-nav"
        :class="
            openMobile
                ? 'fixed inset-0 z-50 min-[1081px]:static min-[1081px]:z-auto'
                : 'hidden min-[1081px]:contents'
        "
    >
        <div
            aria-hidden="true"
            class="hidden w-[230px] shrink-0 min-[1081px]:block"
        />
        <div
            v-if="openMobile"
            aria-hidden="true"
            class="absolute inset-0 bg-black/60 min-[1081px]:hidden"
            @click="setOpenMobile(false)"
        />
        <!-- Desktop geometry (fixed offsets) must stay in sync with the
             AppShell max-width/gutters, the header height, and the layout
             top/bottom padding; the placeholder div above holds the column. -->
        <Sidebar
            collapsible="none"
            variant="sidebar"
            :role="openMobile ? 'dialog' : undefined"
            :aria-modal="openMobile ? 'true' : undefined"
            :aria-label="openMobile ? 'Site navigation' : undefined"
            class="relative h-full w-[280px] max-w-[85vw] shrink-0 overflow-y-auto border-r border-dashed pb-4 min-[1081px]:fixed min-[1081px]:top-20 min-[1081px]:bottom-10 min-[1081px]:left-[max(18px,calc((100vw-1400px)/2+18px))] min-[1081px]:h-auto min-[1081px]:w-[230px] min-[1081px]:pr-5 min-[1081px]:pb-0"
        >
            <SidebarContent class="flex-none">
                <div class="px-2 py-1.5" v-if="page.props.currentTeam">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                data-testid="global-search-trigger"
                                tooltip="Search"
                                aria-label="Search"
                                class="bg-sidebar-accent/50 text-xs text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                                @click="searchOpen = true"
                            >
                                <Search class="h-3.5 w-3.5 opacity-70" />
                                <span>Search</span>
                                <span
                                    class="ml-auto hidden items-center gap-0.5 text-[10px] opacity-60 lg:flex"
                                >
                                    <kbd
                                        class="rounded bg-sidebar px-1 py-0.5 font-sans"
                                        >Ctrl</kbd
                                    >
                                    <kbd
                                        class="rounded bg-sidebar px-1 py-0.5 font-sans"
                                        >K</kbd
                                    >
                                </span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </div>
                <NavMain :items="mainNavItems" />
            </SidebarContent>

            <SidebarFooter class="mt-[22px] border-t border-dashed">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    </div>
    <slot />
    <SearchOverlay v-model:open="searchOpen" />
</template>
