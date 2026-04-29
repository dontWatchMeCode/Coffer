import type { RecordTag } from './record-tags';

export type CollectionItem = {
    id: number;
    title: string;
    description?: string | null;
    tags: RecordTag[];
    createdAt?: string | null;
    updatedAt?: string | null;
};
