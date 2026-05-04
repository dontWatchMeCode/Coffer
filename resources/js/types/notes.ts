import type { RecordTag } from './record-tags';

export type NoteFormat = 'text' | 'excalidraw';

export type JsonValue =
    | string
    | number
    | boolean
    | null
    | JsonValue[]
    | { [key: string]: JsonValue };

export type ExcalidrawScene = {
    type?: 'excalidraw';
    version?: number;
    source?: string;
    elements?: Record<string, JsonValue>[];
    appState?: Record<string, JsonValue>;
    files?: Record<string, JsonValue>;
};

export type NoteItem = {
    id: number;
    title: string;
    body?: string | null;
    format: NoteFormat;
    drawingData?: ExcalidrawScene | null;
    excerpt?: string | null;
    tags: RecordTag[];
    createdAt?: string | null;
    updatedAt?: string | null;
};
