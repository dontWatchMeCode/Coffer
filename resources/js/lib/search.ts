export type SearchResultItem = {
    id: number;
    title: string;
    subtitle: string | null;
    url: string;
};

export type SearchResponse = {
    tasks: SearchResultItem[];
    contacts: SearchResultItem[];
    events: SearchResultItem[];
    projects: SearchResultItem[];
    bookmarks: SearchResultItem[];
    subscriptions: SearchResultItem[];
    notes: SearchResultItem[];
    files: SearchResultItem[];
    collections: SearchResultItem[];
    log_entries: SearchResultItem[];
    spreadsheets: SearchResultItem[];
};

export type SearchCategoryKey = keyof SearchResponse;

export const SEARCH_CATEGORY_KEYS: SearchCategoryKey[] = [
    'tasks',
    'contacts',
    'events',
    'projects',
    'bookmarks',
    'subscriptions',
    'notes',
    'files',
    'collections',
    'log_entries',
    'spreadsheets',
];

export type FlattenedSearchResult = SearchResultItem & {
    category: SearchCategoryKey;
};

export function createEmptySearchResults(): SearchResponse {
    return {
        tasks: [],
        contacts: [],
        events: [],
        projects: [],
        bookmarks: [],
        subscriptions: [],
        notes: [],
        files: [],
        collections: [],
        log_entries: [],
        spreadsheets: [],
    };
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
