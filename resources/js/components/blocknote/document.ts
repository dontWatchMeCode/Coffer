import type {
    DefaultBlockSchema,
    DefaultInlineContentSchema,
    DefaultStyleSchema,
    PartialBlock,
} from '@blocknote/core';

export type BlockNoteDocument = PartialBlock<
    DefaultBlockSchema,
    DefaultInlineContentSchema,
    DefaultStyleSchema
>[];

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

export function parseBlockNoteBody(
    body: string | null | undefined,
): BlockNoteDocument | null {
    if (!body) {
        return null;
    }

    try {
        const parsed = JSON.parse(body);

        if (Array.isArray(parsed)) {
            return normalizeBlockNoteDocument(parsed);
        }
    } catch {
        return [{ type: 'paragraph', content: body }];
    }

    return null;
}

export function isSerializedBlockNoteBody(
    body: string | null | undefined,
): boolean {
    if (!body) {
        return false;
    }

    return body.trimStart().startsWith('[');
}
