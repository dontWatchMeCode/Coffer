import MarkdownIt from 'markdown-it';

type LegacyInlineContent =
    | string
    | Array<{
          type?: string;
          text?: string;
          href?: string;
          content?: LegacyInlineContent;
          styles?: {
              bold?: boolean;
              italic?: boolean;
              strike?: boolean;
              code?: boolean;
          };
      }>;

type LegacyBlock = {
    type?: string;
    content?: LegacyInlineContent;
    children?: LegacyBlock[];
    props?: {
        level?: number;
        checked?: boolean;
        language?: string;
    };
};

const markdown = new MarkdownIt({
    breaks: true,
    html: false,
    linkify: true,
});

const EMPTY_PARAGRAPH_MARKER = '\uE000';

function isLegacySerializedBlockNoteBody(body: string): boolean {
    return body.trimStart().startsWith('[');
}

function escapeMarkdown(text: string): string {
    return text.replace(/([*_`~\[\]()])/g, '\\$1');
}

function normalizeInlineContent(
    content: LegacyInlineContent | undefined,
): string {
    if (!content) {
        return '';
    }

    if (typeof content === 'string') {
        return content;
    }

    return content
        .map((item) => {
            if (item.type === 'link') {
                const label = normalizeInlineContent(item.content);

                return `[${label}](${item.href ?? ''})`;
            }

            const base = item.text ?? normalizeInlineContent(item.content);

            if (!base) {
                return '';
            }

            let formatted = escapeMarkdown(base);

            if (item.styles?.code) {
                formatted = `\`${formatted}\``;
            }

            if (item.styles?.bold) {
                formatted = `**${formatted}**`;
            }

            if (item.styles?.italic) {
                formatted = `*${formatted}*`;
            }

            if (item.styles?.strike) {
                formatted = `~~${formatted}~~`;
            }

            return formatted;
        })
        .join('');
}

function renderLegacyBlocks(blocks: LegacyBlock[], depth = 0): string {
    return blocks
        .map((block) => {
            const content = normalizeInlineContent(block.content);
            const nested =
                block.children && block.children.length > 0
                    ? `\n${renderLegacyBlocks(block.children, depth + 1)}`
                    : '';
            const indent = '  '.repeat(depth);

            switch (block.type) {
                case 'heading':
                    return `${'#'.repeat(block.props?.level ?? 2)} ${content}`;
                case 'bulletListItem':
                    return `${indent}- ${content}${nested}`;
                case 'numberedListItem':
                    return `${indent}1. ${content}${nested}`;
                case 'checkListItem':
                    return `${indent}- [${block.props?.checked ? 'x' : ' '}] ${content}${nested}`;
                case 'quote':
                    return `${indent}> ${content}${nested}`;
                case 'codeBlock': {
                    const language = block.props?.language ?? '';

                    return `\`\`\`${language}\n${content}\n\`\`\``;
                }
                case 'paragraph':
                default:
                    return `${content}${nested}`.trimEnd();
            }
        })
        .filter(Boolean)
        .join('\n\n');
}

function convertLegacyBlockNoteJsonToMarkdown(body: string): string {
    try {
        const parsed = JSON.parse(body) as LegacyBlock[];

        if (!Array.isArray(parsed)) {
            return body;
        }

        return renderLegacyBlocks(parsed).trim();
    } catch {
        return body;
    }
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
    if (!body) {
        return '';
    }

    if (isLegacySerializedBlockNoteBody(body)) {
        return convertLegacyBlockNoteJsonToMarkdown(body);
    }

    return body;
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

    return markdown
        .render(normalized)
        .replaceAll(`<p>${EMPTY_PARAGRAPH_MARKER}</p>`, '<br>');
}
