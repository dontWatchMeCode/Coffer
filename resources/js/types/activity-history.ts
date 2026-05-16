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

export type BlockPayload = Record<string, unknown> | null;

export type BlockChange = {
    type: string;
    position: number;
    payload?: BlockPayload;
};

export type UpdatedBlockChange = {
    type: string;
    position: number;
    old_payload?: BlockPayload;
    payload?: BlockPayload;
};

export type BlockChanges = {
    added?: BlockChange[];
    updated?: UpdatedBlockChange[];
    removed?: BlockChange[];
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
    blockChanges: BlockChanges | null;
};
