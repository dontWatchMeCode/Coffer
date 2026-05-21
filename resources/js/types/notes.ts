import type { RecordTag } from './record-tags';

export type RteBlockType = 'text' | 'excalidraw' | 'mermaid';

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

export type TextPayload = {
    content?: string;
};

export type ExcalidrawPayload = {
    scene?: ExcalidrawScene;
};

export type MermaidPayload = {
    content?: string;
};

export type RteBlock = {
    id: number;
    type: RteBlockType;
    position: number;
    payload: TextPayload | ExcalidrawPayload | MermaidPayload | null;
};

export type NoteItem = {
    id: number;
    title: string;
    blocks: RteBlock[];
    excerpt?: string | null;
    tags: RecordTag[];
    createdAt?: string | null;
    updatedAt?: string | null;
    deletedAt?: string | null;
};
