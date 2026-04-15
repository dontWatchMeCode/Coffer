import { BlockNoteEditor } from '@blocknote/core';
import type { BlockNoteDocument } from '@/components/blocknote/document';
import { normalizeBlockNoteDocument } from '@/components/blocknote/document';

let blockNoteHtmlRenderer: BlockNoteEditor | null = null;

function getBlockNoteHtmlRenderer(): BlockNoteEditor {
    if (blockNoteHtmlRenderer === null) {
        blockNoteHtmlRenderer = BlockNoteEditor.create();
    }

    return blockNoteHtmlRenderer;
}

export function renderBlockNoteDocumentAsHtml(
    content?: BlockNoteDocument | null,
): string {
    return getBlockNoteHtmlRenderer().blocksToHTMLLossy(
        normalizeBlockNoteDocument(content),
    );
}
