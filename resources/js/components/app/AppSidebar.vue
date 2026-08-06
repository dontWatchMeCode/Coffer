<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogo from '@/components/app/AppLogo.vue';
import NavMain from '@/components/nav/NavMain.vue';
import NavUser from '@/components/nav/NavUser.vue';
import SearchOverlay from '@/components/nav/SearchOverlay.vue';
import TeamSwitcher from '@/components/nav/TeamSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
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
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenu>
                <SidebarMenuItem>
                    <TeamSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
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

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
    <SearchOverlay v-model:open="searchOpen" />
</template>
