<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bookmark,
    CalendarDays,
    Contact,
    CreditCard,
    FileText,
    Files,
    Layers3,
    LayoutGrid,
    ListTodo,
    MessageSquareText,
    Search,
    Table2,
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
import { index as teamFiles } from '@/routes/team/files/index';
import { index as teamLog } from '@/routes/team/log/index';
import { index as teamNotes } from '@/routes/team/notes/index';
import { index as teamSpreadsheets } from '@/routes/team/spreadsheets/index';
import { index as teamSubscriptions } from '@/routes/team/subscriptions/index';
import { index as teamTasks } from '@/routes/team/tasks/index';
import type { NavItem } from '@/types';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? teamDashboard(page.props.currentTeam.slug).url
        : dashboard().url,
);

function featureEnabled(feature: string): boolean {
    return page.props.currentTeam?.featureSettings?.[feature] ?? true;
}

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
                  feature: 'tasks',
              },
              {
                  title: 'Calendar',
                  href: teamCalendar(page.props.currentTeam.slug).url,
                  icon: CalendarDays,
                  feature: 'calendar',
              },
              {
                  title: 'Contacts',
                  href: teamContacts(page.props.currentTeam.slug).url,
                  icon: Contact,
                  feature: 'contacts',
              },
              {
                  title: 'Bookmarks',
                  href: teamBookmarks(page.props.currentTeam.slug).url,
                  icon: Bookmark,
                  feature: 'bookmarks',
              },
              {
                  title: 'Subscriptions',
                  href: teamSubscriptions(page.props.currentTeam.slug).url,
                  icon: CreditCard,
                  feature: 'subscriptions',
              },
              {
                  title: 'Notes',
                  href: teamNotes(page.props.currentTeam.slug).url,
                  icon: FileText,
                  feature: 'notes',
              },
              {
                  title: 'Files',
                  href: teamFiles(page.props.currentTeam.slug).url,
                  icon: Files,
                  feature: 'files',
              },
              {
                  title: 'Log',
                  href: teamLog(page.props.currentTeam.slug).url,
                  icon: MessageSquareText,
                  feature: 'log',
              },
              {
                  title: 'Spreadsheets',
                  href: teamSpreadsheets(page.props.currentTeam.slug).url,
                  icon: Table2,
                  feature: 'spreadsheets',
              },
              {
                  title: 'Collections',
                  href: teamCollections(page.props.currentTeam.slug).url,
                  icon: Layers3,
                  feature: 'collections',
              },
          ].filter(
              (item) => !('feature' in item) || featureEnabled(item.feature),
          )
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
    <SearchOverlay v-model:open="searchOpen" />
</template>
