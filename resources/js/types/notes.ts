import type { RecordTag } from './record-tags';

export type NoteItem = {
    id: number;
    title: string;
    body?: string | null;
    excerpt?: string | null;
    tags: RecordTag[];
    createdAt?: string | null;
    updatedAt?: string | null;
};
