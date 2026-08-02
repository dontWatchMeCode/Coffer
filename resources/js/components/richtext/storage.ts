import DOMPurify from 'dompurify';
import MarkdownIt from 'markdown-it';

const markdown = new MarkdownIt({
    breaks: true,
    html: false,
    linkify: true,
});

const EMPTY_PARAGRAPH_MARKER = '\uE000';

type HtmlSanitizer = {
    sanitize: (html: string) => string;
};

type DomPurifyFactory = (window: Window) => unknown;

function hasSanitize(value: unknown): value is HtmlSanitizer {
    return (
        (typeof value === 'object' || typeof value === 'function') &&
        value !== null &&
        'sanitize' in value &&
        typeof value.sanitize === 'function'
    );
}

function getDefaultExport(value: unknown): unknown {
    if (typeof value !== 'object' || value === null || !('default' in value)) {
        return undefined;
    }

    return value.default;
}

function createSanitizer(value: unknown): HtmlSanitizer | undefined {
    if (hasSanitize(value)) {
        return value;
    }

    if (typeof window !== 'undefined' && typeof value === 'function') {
        const purifier = (value as DomPurifyFactory)(window);

        if (hasSanitize(purifier)) {
            return purifier;
        }
    }

    return undefined;
}

function sanitizeHtml(html: string): string {
    const sanitizer =
        createSanitizer(DOMPurify) ??
        createSanitizer(getDefaultExport(DOMPurify));

    if (sanitizer) {
        return sanitizer.sanitize(html);
    }

    if (typeof window === 'undefined' && typeof document === 'undefined') {
        return html;
    }

    throw new Error('DOMPurify sanitizer is unavailable.');
}

function renderMarkdownAsHtml(body: string): string {
    return markdown
        .render(body)
        .replaceAll(`<p>${EMPTY_PARAGRAPH_MARKER}</p>`, '<br>');
}

function preserveEmptyParagraphsInMarkdown(body: string): string {
    const lines = body.replace(/\r\n?/g, '\n').trim().split('\n');
    const normalizedLines: string[] = [];
    let blankLineCount = 0;

    for (const line of lines) {
        if (line.trim() === '') {
            blankLineCount += 1;

            continue;
        }

        if (blankLineCount > 0) {
            normalizedLines.push('');

            const emptyParagraphCount = Math.max(
                0,
                Math.floor((blankLineCount - 1) / 2),
            );

            for (let index = 0; index < emptyParagraphCount; index += 1) {
                normalizedLines.push(EMPTY_PARAGRAPH_MARKER, '');
            }

            blankLineCount = 0;
        }

        normalizedLines.push(line);
    }

    return normalizedLines.join('\n');
}

export function normalizeStoredRichText(
    body: string | null | undefined,
): string {
    return body ?? '';
}

export function trimStoredRichText(body: string | null | undefined): string {
    let text = normalizeStoredRichText(body).trim();

    while (text.endsWith('&nbsp;') || text.endsWith('\u00a0')) {
        text = text.replace(/(?:&nbsp;|\u00a0)\s*$/, '');
        text = text.trimEnd();
    }

    return text;
}

export function renderStoredRichTextAsHtml(
    body: string | null | undefined,
): string {
    const normalized = preserveEmptyParagraphsInMarkdown(
        normalizeStoredRichText(body),
    );

    if (!normalized.trim()) {
        return '';
    }

    return sanitizeHtml(renderMarkdownAsHtml(normalized));
}
