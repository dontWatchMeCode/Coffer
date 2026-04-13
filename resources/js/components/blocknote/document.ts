import type { PartialBlock } from '@blocknote/core';

export type BlockNoteDocument = PartialBlock[];

export const defaultBlockNoteDocument: BlockNoteDocument = [
    { type: 'paragraph', content: '' },
];

export function normalizeBlockNoteDocument(
    content?: BlockNoteDocument | null,
): BlockNoteDocument {
    if (!content || content.length === 0) {
        return defaultBlockNoteDocument;
    }

    return content;
}

export function serializeBlockNoteDocument(
    content?: BlockNoteDocument | null,
): string {
    return JSON.stringify(normalizeBlockNoteDocument(content));
}
