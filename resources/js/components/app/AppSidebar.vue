<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bookmark,
    CalendarDays,
    Contact,
    FileText,
    KeyRound,
    Layers3,
    LayoutGrid,
    ListTodo,
    Search,
} from 'lucide-vue-next';
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
import { dashboard } from '@/routes';
import { dashboard as teamDashboard } from '@/routes/team';
import { index as teamBookmarks } from '@/routes/team/bookmarks/index';
import { index as teamCalendar } from '@/routes/team/calendar/index';
import { index as teamCollections } from '@/routes/team/collections/index';
import { index as teamContacts } from '@/routes/team/contacts/index';
import { index as teamMcp } from '@/routes/team/mcp/index';
import { index as teamNotes } from '@/routes/team/notes/index';
import { index as teamTasks } from '@/routes/team/tasks/index';
import type { NavItem } from '@/types';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? teamDashboard(page.props.currentTeam.slug).url
        : dashboard().url,
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl.value,
        icon: LayoutGrid,
    },
    ...(page.props.currentTeam
        ? [
              {
                  title: 'Tasks',
                  href: teamTasks(page.props.currentTeam.slug).url,
                  icon: ListTodo,
              },
              {
                  title: 'Calendar',
                  href: teamCalendar(page.props.currentTeam.slug).url,
                  icon: CalendarDays,
              },
              {
                  title: 'Contacts',
                  href: teamContacts(page.props.currentTeam.slug).url,
                  icon: Contact,
              },
              {
                  title: 'Bookmarks',
                  href: teamBookmarks(page.props.currentTeam.slug).url,
                  icon: Bookmark,
              },
              {
                  title: 'Notes',
                  href: teamNotes(page.props.currentTeam.slug).url,
                  icon: FileText,
              },
              {
                  title: 'Collections',
                  href: teamCollections(page.props.currentTeam.slug).url,
                  icon: Layers3,
              },
          ]
        : []),
]);

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
            <SidebarMenu v-if="page.props.currentTeam">
                <SidebarMenuItem>
                    <SidebarMenuButton as-child tooltip="MCP">
                        <Link :href="teamMcp(page.props.currentTeam.slug).url">
                            <KeyRound />
                            <span>MCP</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
    <SearchOverlay v-model:open="searchOpen" />
</template>
