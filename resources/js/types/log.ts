export type LogEntryItem = {
    id: number;
    body: string;
    category?: string | null;
    createdAt?: string | null;
    deletedAt?: string | null;
};
