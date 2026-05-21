export type BookmarkItem = {
    id: number;
    title: string;
    url: string;
    description?: string | null;
    notes?: string | null;
    createdAt?: string | null;
    updatedAt?: string | null;
    deletedAt?: string | null;
};
