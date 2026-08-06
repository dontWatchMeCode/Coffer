import {
    Bookmark,
    CalendarDays,
    Contact,
    CreditCard,
    Files,
    FileText,
    FolderGit2,
    Layers3,
    ListTodo,
    ScrollText,
    Table2,
} from 'lucide-vue-next';
import type { LucideIcon } from 'lucide-vue-next';

export type SearchResultItem = {
    id: number;
    title: string;
    subtitle: string | null;
    url: string;
};

type SearchCategoryDefinition = {
    key: string;
    label: string;
    prefix: string;
    icon: LucideIcon;
};

export const SEARCH_CATEGORIES = [
    {
        key: 'tasks',
        label: 'Tasks',
        prefix: 't',
        icon: ListTodo,
    },
    {
        key: 'contacts',
        label: 'Contacts',
        prefix: 'c',
        icon: Contact,
    },
    {
        key: 'events',
        label: 'Events',
        prefix: 'e',
        icon: CalendarDays,
    },
    {
        key: 'projects',
        label: 'Projects',
        prefix: 'p',
        icon: FolderGit2,
    },
    {
        key: 'bookmarks',
        label: 'Bookmarks',
        prefix: 'b',
        icon: Bookmark,
    },
    {
        key: 'subscriptions',
        label: 'Subscriptions',
        prefix: 's',
        icon: CreditCard,
    },
    {
        key: 'notes',
        label: 'Notes',
        prefix: 'n',
        icon: FileText,
    },
    {
        key: 'files',
        label: 'Files',
        prefix: 'f',
        icon: Files,
    },
    {
        key: 'collections',
        label: 'Collections',
        prefix: 'l',
        icon: Layers3,
    },
    {
        key: 'log_entries',
        label: 'Log',
        prefix: 'g',
        icon: ScrollText,
    },
    {
        key: 'spreadsheets',
        label: 'Spreadsheets',
        prefix: 'x',
        icon: Table2,
    },
] as const satisfies readonly SearchCategoryDefinition[];

export type SearchCategory = (typeof SEARCH_CATEGORIES)[number];

export type SearchCategoryKey = SearchCategory['key'];

export type SearchResponse = Record<SearchCategoryKey, SearchResultItem[]>;

export const SEARCH_CATEGORY_KEYS = SEARCH_CATEGORIES.map(
    (category) => category.key,
);

export type SearchTagItem = { id: number; name: string; slug: string };

export type SearchTypeOption = {
    value: SearchCategoryKey;
    label: string;
    prefix: string;
};

export type FlattenedSearchResult = SearchResultItem & {
    category: SearchCategoryKey;
};

export function createEmptySearchResults(): SearchResponse {
    const results = {} as SearchResponse;

    for (const key of SEARCH_CATEGORY_KEYS) {
        results[key] = [];
    }

    return results;
}

export function searchCategory(key: string): SearchCategory | undefined {
    return SEARCH_CATEGORIES.find((category) => category.key === key);
}

export function flattenSearchResults(
    results: SearchResponse,
): FlattenedSearchResult[] {
    const items: FlattenedSearchResult[] = [];

    for (const key of SEARCH_CATEGORY_KEYS) {
        for (const item of results[key]) {
            items.push({ ...item, category: key });
        }
    }

    return items;
}
