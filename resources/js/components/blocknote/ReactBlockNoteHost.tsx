import '@blocknote/core/fonts/inter.css';
import { BlockNoteView } from '@blocknote/mantine';
import '@blocknote/mantine/style.css';
import { useCreateBlockNote } from '@blocknote/react';
import { createElement, type FocusEventHandler } from 'react';
import { useEffect, useRef } from 'react';
import {
    normalizeBlockNoteDocument,
    serializeBlockNoteDocument,
    type BlockNoteDocument,
} from '@/components/blocknote/document';

type ReactBlockNoteHostProps = {
    content?: BlockNoteDocument | null;
    editable: boolean;
    placeholder?: string;
    onBlur?: () => void;
    onChange?: (value: BlockNoteDocument) => void;
    onFocus?: () => void;
    onReady?: () => void;
};

export default function ReactBlockNoteHost({
    content,
    editable,
    placeholder,
    onBlur,
    onChange,
    onFocus,
    onReady,
}: ReactBlockNoteHostProps) {
    const editor = useCreateBlockNote(
        {
            initialContent: normalizeBlockNoteDocument(content),
            placeholders: placeholder
                ? {
                      default: placeholder,
                      emptyDocument: placeholder,
                  }
                : undefined,
        },
        [],
    );
    const isApplyingExternalUpdate = useRef(false);
    const lastSerializedDocument = useRef(serializeBlockNoteDocument(content));
    const readyEmitted = useRef(false);
    const handleBlur: FocusEventHandler<HTMLDivElement> = (event) => {
        if (!event.currentTarget.contains(event.relatedTarget)) {
            onBlur?.();
        }
    };
    const handleFocus: FocusEventHandler<HTMLDivElement> = (event) => {
        if (!event.currentTarget.contains(event.relatedTarget)) {
            onFocus?.();
        }
    };

    useEffect(() => {
        if (readyEmitted.current) {
            return;
        }

        readyEmitted.current = true;
        lastSerializedDocument.current = serializeBlockNoteDocument(
            editor.document,
        );
        onReady?.();
    }, [editor]);

    useEffect(() => {
        const incomingDocument = serializeBlockNoteDocument(content);

        if (incomingDocument === lastSerializedDocument.current) {
            return;
        }

        isApplyingExternalUpdate.current = true;
        editor.replaceBlocks(
            editor.document,
            normalizeBlockNoteDocument(content),
        );
        lastSerializedDocument.current = incomingDocument;
    }, [content, editor]);

    return createElement(
        'div',
        {
            className: 'w-full',
            onBlur: handleBlur,
            onFocus: handleFocus,
        },
        createElement(BlockNoteView, {
            editor,
            editable,
            onChange: () => {
                const nextDocument = editor.document as BlockNoteDocument;
                const serializedDocument =
                    serializeBlockNoteDocument(nextDocument);

                if (isApplyingExternalUpdate.current) {
                    isApplyingExternalUpdate.current = false;
                    lastSerializedDocument.current = serializedDocument;

                    return;
                }

                if (serializedDocument === lastSerializedDocument.current) {
                    return;
                }

                lastSerializedDocument.current = serializedDocument;
                onChange?.(nextDocument);
            },
        }),
    );
}
