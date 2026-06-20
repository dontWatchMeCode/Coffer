import type { ActivityHistoryConfig } from './activity-history';

export type LogEntryItem = {
    id: number;
    body: string;
    category?: string | null;
    createdAt?: string | null;
    deletedAt?: string | null;
    activityHistory?: ActivityHistoryConfig | null;
};

export type LogSeparatorItem = {
    type: 'separator';
    label: string;
    key: string;
};

export type LogTimelineEntryItem = {
    type: 'entry';
    entry: LogEntryItem;
    key: string;
};

export type LogTimelineItem = LogSeparatorItem | LogTimelineEntryItem;
