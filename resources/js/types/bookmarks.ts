export type BookmarkItem = {
    id: number;
    title: string;
    url: string;
    description?: string | null;
    tags?: string[] | null;
    notes?: string | null;
    isArchived: boolean;
    createdAt?: string | null;
    updatedAt?: string | null;
};
