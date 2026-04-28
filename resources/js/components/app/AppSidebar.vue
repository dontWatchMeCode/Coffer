<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Bookmark,
    CalendarDays,
    Contact,
    FileText,
    FolderGit2,
    LayoutGrid,
    ListTodo,
    Search,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogo from '@/components/app/AppLogo.vue';
import NavFooter from '@/components/nav/NavFooter.vue';
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
import { index as teamContacts } from '@/routes/team/contacts/index';
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
          ]
        : []),
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

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
                <button
                    @click="searchOpen = true"
                    class="flex h-8 w-full items-center gap-2 rounded-md bg-sidebar-accent/50 px-2.5 text-xs text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                >
                    <Search class="h-3.5 w-3.5 shrink-0 opacity-70" />
                    <span class="flex-1 text-left">Search</span>
                    <span
                        class="hidden items-center gap-0.5 text-[10px] opacity-60 lg:flex"
                    >
                        <kbd class="rounded bg-sidebar px-1 py-0.5 font-sans"
                            >Ctrl</kbd
                        >
                        <kbd class="rounded bg-sidebar px-1 py-0.5 font-sans"
                            >K</kbd
                        >
                    </span>
                </button>
            </div>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
    <SearchOverlay v-model:open="searchOpen" />
</template>
