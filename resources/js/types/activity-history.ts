export type ActivityHistoryItem = {
    id: number;
    event: string | null;
    description: string;
    changedFields: string[];
    causerName: string | null;
    createdAt: string;
    old: Record<string, unknown> | null;
    attributes: Record<string, unknown> | null;
};
