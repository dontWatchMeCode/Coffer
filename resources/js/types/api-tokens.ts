export type ApiTokenPermission = 'none' | 'read' | 'write';

export type ApiTokenAbilities = {
    collections: ApiTokenPermission;
    notes: ApiTokenPermission;
    bookmarks: ApiTokenPermission;
    contacts: ApiTokenPermission;
    calendar: ApiTokenPermission;
    tasks: ApiTokenPermission;
    task_projects: {
        mode: 'all' | 'only';
        ids: number[];
    };
};

export type ApiTokenItem = {
    id: number;
    name: string;
    token: string;
    abilities: ApiTokenAbilities;
    created_by: string | null;
    created_at: string | null;
    last_used_at: string | null;
    expires_at: string | null;
};

export type ApiTokenProject = {
    id: number;
    name: string;
};

export const apiTokenResourceLabels: Record<
    keyof Omit<ApiTokenAbilities, 'task_projects'>,
    string
> = {
    collections: 'Collections',
    notes: 'Notes',
    bookmarks: 'Bookmarks',
    contacts: 'Contacts',
    calendar: 'Calendar',
    tasks: 'Tasks',
};
