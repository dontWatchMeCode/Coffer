export const taskInputLikeClass =
    'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive';

type TaskStatusMeta = {
    label: string;
    triggerColor: string;
    badgeColor: string;
    icon: 'flag' | 'activity' | 'ban' | 'check' | 'trash' | 'help';
};

export const taskStatusMeta: Record<string, TaskStatusMeta> = {
    planned: {
        label: 'Planned',
        triggerColor: 'bg-slate-500',
        badgeColor: 'text-slate-500',
        icon: 'flag',
    },
    in_progress: {
        label: 'In Progress',
        triggerColor: 'bg-yellow-500',
        badgeColor: 'text-yellow-500',
        icon: 'activity',
    },
    on_hold: {
        label: 'On Hold',
        triggerColor: 'bg-orange-500',
        badgeColor: 'text-orange-500',
        icon: 'ban',
    },
    completed: {
        label: 'Completed',
        triggerColor: 'bg-green-500',
        badgeColor: 'text-green-500',
        icon: 'check',
    },
    dropped: {
        label: 'Dropped',
        triggerColor: 'bg-red-500',
        badgeColor: 'text-red-500',
        icon: 'trash',
    },
    question: {
        label: 'Question',
        triggerColor: 'bg-purple-500',
        badgeColor: 'text-purple-500',
        icon: 'help',
    },
};

export function getTaskStatusMeta(status: string): TaskStatusMeta {
    return (
        taskStatusMeta[status] ?? {
            label: status
                .split('_')
                .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' '),
            triggerColor: 'bg-gray-500',
            badgeColor: 'text-gray-500',
            icon: 'flag',
        }
    );
}

export function formatRelativeTime(
    dateString: string | null | undefined,
    now?: Date,
): string {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);
    const _now = now ?? new Date();
    const diffMs = _now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) {
        return 'just now';
    }

    if (diffMins < 60) {
        return `${diffMins}m ago`;
    }

    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }

    if (diffDays < 30) {
        return `${diffDays}d ago`;
    }

    return date.toLocaleDateString();
}

export function formatExactDateTime(
    dateString: string | null | undefined,
): string {
    if (!dateString) {
        return '';
    }

    return new Date(dateString).toLocaleString();
}

export function formatTimeEstimate(minutes: number | null | undefined): string {
    if (!minutes) {
        return '';
    }

    const h = Math.floor(minutes / 60);
    const m = minutes % 60;

    if (h === 0) {
        return `${m}m`;
    }

    if (m === 0) {
        return `${h}h`;
    }

    return `${h}h ${m}m`;
}

export function formatTimeEstimateInput(
    minutes: number | null | undefined,
): string {
    if (minutes === null || minutes === undefined) {
        return '';
    }

    return `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')}`;
}

export function parseTimeEstimate(input: string): number | null {
    const trimmed = input.trim();

    if (!trimmed) {
        return null;
    }

    const colonMatch = trimmed.match(/^(\d+):(\d{1,2})$/);

    if (colonMatch) {
        const minutes = Number(colonMatch[2]);

        return minutes > 59 ? null : Number(colonMatch[1]) * 60 + minutes;
    }

    const hmMatch = trimmed.match(/^(\d+)h(\d{1,2})m?$/i);

    if (hmMatch) {
        const minutes = Number(hmMatch[2]);

        return minutes > 59 ? null : Number(hmMatch[1]) * 60 + minutes;
    }

    const hoursOnly = trimmed.match(/^(\d+)h$/i);

    if (hoursOnly) {
        return Number(hoursOnly[1]) * 60;
    }

    const minutesOnly = trimmed.match(/^(\d+)m?$/i);

    if (minutesOnly) {
        return Number(minutesOnly[1]);
    }

    return null;
}
