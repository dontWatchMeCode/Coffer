export type ApiTokenPermission = 'none' | 'read' | 'write';

export type ApiTokenResource =
    | 'tasks'
    | 'calendar'
    | 'contacts'
    | 'bookmarks'
    | 'subscriptions'
    | 'notes'
    | 'collections'
    | 'log_entries'
    | 'files';

export type ApiTokenProjectScope = {
    mode: 'all' | 'only';
    ids: number[];
};

export type ApiTokenAbilities = Record<ApiTokenResource, ApiTokenPermission> & {
    task_projects: ApiTokenProjectScope;
};

export type ApiTokenResourceLabels = Record<ApiTokenResource, string>;

export type ApiTokenFormData = {
    name: string;
    expires_at: string;
    abilities: ApiTokenAbilities;
};

export type ApiTokenItem = {
    id: number;
    name: string;
    token: string | null;
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

export function apiTokenResourceKeys(
    labels: ApiTokenResourceLabels,
): ApiTokenResource[] {
    return Object.keys(labels) as ApiTokenResource[];
}

export function createApiTokenAbilities(
    resources: readonly ApiTokenResource[],
): ApiTokenAbilities {
    const abilities = {} as Record<ApiTokenResource, ApiTokenPermission>;

    for (const resource of resources) {
        abilities[resource] = 'none';
    }

    return {
        ...abilities,
        task_projects: { mode: 'all', ids: [] },
    };
}
