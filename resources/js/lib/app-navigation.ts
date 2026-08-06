import {
    Bookmark,
    CalendarDays,
    Contact,
    CreditCard,
    FileText,
    Files,
    Layers3,
    ListTodo,
    MessageSquareText,
    Table2,
} from 'lucide-vue-next';
import type { LucideIcon } from 'lucide-vue-next';
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
import type { TeamFeatureKey } from '@/types/teams';

type TeamNavigationDefinition = {
    feature: TeamFeatureKey;
    title: string;
    icon: LucideIcon;
    href: (teamSlug: string) => string;
};

export const TEAM_NAVIGATION_ITEMS = [
    {
        feature: 'tasks',
        title: 'Tasks',
        icon: ListTodo,
        href: (teamSlug: string) => teamTasks(teamSlug).url,
    },
    {
        feature: 'calendar',
        title: 'Calendar',
        icon: CalendarDays,
        href: (teamSlug: string) => teamCalendar(teamSlug).url,
    },
    {
        feature: 'contacts',
        title: 'Contacts',
        icon: Contact,
        href: (teamSlug: string) => teamContacts(teamSlug).url,
    },
    {
        feature: 'bookmarks',
        title: 'Bookmarks',
        icon: Bookmark,
        href: (teamSlug: string) => teamBookmarks(teamSlug).url,
    },
    {
        feature: 'subscriptions',
        title: 'Subscriptions',
        icon: CreditCard,
        href: (teamSlug: string) => teamSubscriptions(teamSlug).url,
    },
    {
        feature: 'notes',
        title: 'Notes',
        icon: FileText,
        href: (teamSlug: string) => teamNotes(teamSlug).url,
    },
    {
        feature: 'files',
        title: 'Files',
        icon: Files,
        href: (teamSlug: string) => teamFiles(teamSlug).url,
    },
    {
        feature: 'log',
        title: 'Log',
        icon: MessageSquareText,
        href: (teamSlug: string) => teamLog(teamSlug).url,
    },
    {
        feature: 'spreadsheets',
        title: 'Spreadsheets',
        icon: Table2,
        href: (teamSlug: string) => teamSpreadsheets(teamSlug).url,
    },
    {
        feature: 'collections',
        title: 'Collections',
        icon: Layers3,
        href: (teamSlug: string) => teamCollections(teamSlug).url,
    },
] as const satisfies readonly TeamNavigationDefinition[];
