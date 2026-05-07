import type { ExcalidrawScene } from './notes';

export type LinkRecord = {
    id: number;
    type: string;
    title: string;
    url: string;
    preview?: string | null;
    format?: string | null;
    drawingData?: ExcalidrawScene | null;
};

export type LinkContext = {
    type: string;
    id: number;
    title: string;
};

export type LinkEndpoints = {
    candidates: string;
    store: string;
    destroy: string;
};
