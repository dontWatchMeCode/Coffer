export type RelationChange = {
    type: 'tag' | 'link';
    action: 'added' | 'removed' | 'sync';
    target?: {
        type?: string;
        id?: number;
        title?: string;
        url?: string;
        name?: string;
    };
    added?: string[];
    removed?: string[];
};

export type ActivityHistoryItem = {
    id: number;
    event: string | null;
    description: string;
    changedFields: string[];
    causerName: string | null;
    createdAt: string;
    old: Record<string, unknown> | null;
    attributes: Record<string, unknown> | null;
    relationChanges: RelationChange | null;
};
