import type { BookmarkItem } from '@/types/bookmarks';
import type { CalendarEventItem } from '@/types/calendar';
import type { CollectionItem } from '@/types/collections';
import type { ContactEntry, ContactItem } from '@/types/contacts';
import type { LogEntryItem } from '@/types/log';
import type {
    MermaidPayload,
    NoteItem,
    RteBlock,
    TextPayload,
} from '@/types/notes';
import type { LinkRecord } from '@/types/record-links';
import type { RecordTag } from '@/types/record-tags';
import type { SubscriptionItem } from '@/types/subscriptions';
import type { TaskCommentItem, TaskItem } from '@/types/tasks';
import { billingDateLabel } from './subscriptions';

function formatTagList(tags: RecordTag[]): string {
    if (!tags.length) {
        return '';
    }

    return tags.map((t) => `\`${t.name}\``).join(' ');
}

function formatLinkSection(links: LinkRecord[]): string {
    if (!links.length) {
        return '';
    }

    const lines = links.map((l) => `- [${l.title}](${l.url})`);

    return `## Links\n\n${lines.join('\n')}`;
}

function stripHtml(html: string): string {
    if (typeof document === 'undefined') {
        return html.replace(/<[^>]*>/g, '');
    }

    const div = document.createElement('div');
    div.innerHTML = html;

    return div.textContent ?? div.innerText ?? '';
}

function blocksToText(blocks: RteBlock[]): string {
    return blocks
        .map((block) => {
            if (block.type === 'text') {
                return (block.payload as TextPayload | null)?.content ?? '';
            }

            if (block.type === 'mermaid') {
                return (block.payload as MermaidPayload | null)?.content ?? '';
            }

            return '[Drawing]';
        })
        .filter(Boolean)
        .join('\n\n');
}

export function serializeBookmark(
    bookmark: BookmarkItem,
    tags: RecordTag[],
    links: LinkRecord[],
): string {
    const parts: string[] = [`# ${bookmark.title}`];

    parts.push(`\n**URL:** ${bookmark.url}`);

    if (bookmark.description) {
        parts.push(`\n## Description\n\n${bookmark.description}`);
    }

    if (bookmark.notes) {
        parts.push(`\n## Notes\n\n${bookmark.notes}`);
    }

    const tagList = formatTagList(tags);

    if (tagList) {
        parts.push(`\n## Tags\n\n${tagList}`);
    }

    const linkSection = formatLinkSection(links);

    if (linkSection) {
        parts.push(`\n${linkSection}`);
    }

    return parts.join('');
}

export function serializeContact(
    contact: ContactItem,
    tags: RecordTag[],
    links: LinkRecord[],
): string {
    const parts: string[] = [`# ${contact.name}`];

    const entriesSection = (
        label: string,
        entries?: ContactEntry[] | null,
    ): void => {
        if (!entries?.length) {
            return;
        }

        parts.push(`\n## ${label}\n`);

        for (const entry of entries) {
            const lbl = entry.label ? ` (${entry.label})` : '';
            parts.push(`- ${entry.value}${lbl}`);
        }
    };

    entriesSection('Phone Numbers', contact.phoneNumbers);
    entriesSection('Email Addresses', contact.emailAddresses);
    entriesSection('Links', contact.links);

    if (contact.address) {
        parts.push(`\n## Address\n\n${contact.address}`);
    }

    if (contact.additionalInfo) {
        parts.push(`\n## Additional Info\n\n${contact.additionalInfo}`);
    }

    const tagList = formatTagList(tags);

    if (tagList) {
        parts.push(`\n## Tags\n\n${tagList}`);
    }

    const linkSection = formatLinkSection(links);

    if (linkSection) {
        parts.push(`\n${linkSection}`);
    }

    return parts.join('');
}

export function serializeNote(
    note: NoteItem,
    tags: RecordTag[],
    links: LinkRecord[],
): string {
    const parts: string[] = [`# ${note.title}`];

    const sortedBlocks = [...(note.blocks ?? [])].sort(
        (a, b) => a.position - b.position,
    );

    for (const block of sortedBlocks) {
        if (block.type === 'text') {
            const content = (block.payload as { content?: string } | null)
                ?.content;

            if (content) {
                parts.push(`\n\n${content}`);
            }
        } else if (block.type === 'excalidraw') {
            parts.push('\n\n*[Excalidraw drawing]*');
        } else if (block.type === 'mermaid') {
            const content = (block.payload as { content?: string } | null)
                ?.content;

            if (content) {
                parts.push(`\n\n\`\`\`mermaid\n${content}\n\`\`\``);
            }
        }
    }

    const tagList = formatTagList(tags);

    if (tagList) {
        parts.push(`\n\n## Tags\n\n${tagList}\n`);
    }

    const linkSection = formatLinkSection(links);

    if (linkSection) {
        parts.push(`\n${linkSection}`);
    }

    return parts.join('');
}

export function serializeSubscription(
    subscription: SubscriptionItem,
    tags: RecordTag[],
    links: LinkRecord[],
): string {
    const parts: string[] = [`# ${subscription.name}`];

    if (subscription.price) {
        const currency = subscription.currency ?? 'USD';
        const num = parseFloat(subscription.price);
        const formatted = isNaN(num)
            ? subscription.price
            : new Intl.NumberFormat('en-US', {
                  style: 'currency',
                  currency,
              }).format(num);
        const cycle = subscription.billingCycle
            ? ` / ${subscription.billingCycle}`
            : '';
        const status = subscription.isActive ? '' : ' (Inactive)';
        parts.push(`\n**Price:** ${formatted}${cycle}${status}`);
    }

    if (subscription.category) {
        parts.push(`\n**Category:** ${subscription.category}`);
    }

    if (subscription.firstBillingDate) {
        parts.push(
            `\n**First Billing:** ${new Date(subscription.firstBillingDate).toLocaleDateString()}`,
        );
    }

    if (subscription.nextBillingDate) {
        parts.push(
            `\n**${billingDateLabel(subscription.isActive)}:** ${new Date(subscription.nextBillingDate).toLocaleDateString()}`,
        );
    }

    if (subscription.url) {
        parts.push(`\n**URL:** ${subscription.url}`);
    }

    if (subscription.description) {
        parts.push(`\n## Description\n\n${subscription.description}`);
    }

    if (subscription.notes) {
        parts.push(`\n## Notes\n\n${subscription.notes}`);
    }

    const tagList = formatTagList(tags);

    if (tagList) {
        parts.push(`\n## Tags\n\n${tagList}`);
    }

    const linkSection = formatLinkSection(links);

    if (linkSection) {
        parts.push(`\n${linkSection}`);
    }

    return parts.join('');
}

export function serializeCollection(
    collection: CollectionItem,
    tags: RecordTag[],
    links: LinkRecord[],
): string {
    const parts: string[] = [`# ${collection.title}`];

    if (collection.description) {
        parts.push(`\n${collection.description}`);
    }

    const tagList = formatTagList(tags);

    if (tagList) {
        parts.push(`\n## Tags\n\n${tagList}`);
    }

    if (links.length) {
        const grouped = new Map<string, LinkRecord[]>();

        for (const link of links) {
            const existing = grouped.get(link.type) ?? [];
            existing.push(link);
            grouped.set(link.type, existing);
        }

        parts.push('\n## Linked Records\n');

        for (const [type, typeLinks] of grouped) {
            const heading = type
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (c) => c.toUpperCase());
            parts.push(
                `### ${heading.endsWith('s') ? heading : `${heading}s`}\n`,
            );

            for (const l of typeLinks) {
                parts.push(`- [${l.title}](${l.url})`);
            }

            parts.push('');
        }
    }

    return parts.join('');
}

export function serializeCalendarEvent(
    event: CalendarEventItem,
    tags: RecordTag[],
    links: LinkRecord[],
): string {
    const parts: string[] = [`# ${event.title}`];

    if (event.date) {
        const formatted = new Date(
            `${event.date}T00:00:00`,
        ).toLocaleDateString();
        parts.push(`\n**Date:** ${formatted}`);
    }

    if (event.time) {
        parts.push(`\n**Time:** ${event.time}`);
    }

    if (event.description) {
        parts.push(`\n## Description\n\n${event.description}`);
    }

    const tagList = formatTagList(tags);

    if (tagList) {
        parts.push(`\n## Tags\n\n${tagList}`);
    }

    const linkSection = formatLinkSection(links);

    if (linkSection) {
        parts.push(`\n${linkSection}`);
    }

    return parts.join('');
}

export function serializeTask(
    task: TaskItem,
    project: { name: string },
    comments: TaskCommentItem[],
    tags: RecordTag[],
    links: LinkRecord[],
): string {
    const parts: string[] = [`# ${task.title}`];

    parts.push(`\n**Project:** ${project.name}`);
    parts.push(`**Status:** ${task.status}`);

    if (task.assigneeName) {
        parts.push(`**Assignee:** ${task.assigneeName}`);
    }

    if (task.creatorName) {
        parts.push(`**Created by:** ${task.creatorName}`);
    }

    if (task.dueAt) {
        parts.push(`**Due:** ${new Date(task.dueAt).toLocaleDateString()}`);
    }

    if (task.progress > 0) {
        parts.push(`**Progress:** ${task.progress}%`);
    }

    if (task.description) {
        parts.push(`\n## Description\n\n${task.description}`);
    }

    if (comments.length) {
        parts.push('\n## Comments\n');

        for (const c of comments) {
            const author = c.userName ?? 'Unknown';
            const date = c.createdAt
                ? new Date(c.createdAt).toLocaleString()
                : '';
            const body = stripHtml(blocksToText(c.blocks));
            parts.push(`**${author}**${date ? ` — ${date}` : ''}\n> ${body}\n`);
        }
    }

    const tagList = formatTagList(tags);

    if (tagList) {
        parts.push(`\n## Tags\n\n${tagList}`);
    }

    const linkSection = formatLinkSection(links);

    if (linkSection) {
        parts.push(`\n${linkSection}`);
    }

    return parts.join('');
}

export function serializeLogEntry(entry: LogEntryItem): string {
    const parts: string[] = [];

    if (entry.category) {
        parts.push(`**[${entry.category}]** `);
    }

    parts.push(entry.body);

    if (entry.createdAt) {
        parts.push(` — *${new Date(entry.createdAt).toLocaleString()}*`);
    }

    return parts.join('');
}
