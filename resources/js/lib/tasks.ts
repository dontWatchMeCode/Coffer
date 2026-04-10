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
            label: 'Unknown',
            triggerColor: 'bg-gray-500',
            badgeColor: 'text-gray-500',
            icon: 'flag',
        }
    );
}

export function formatRelativeTime(
    dateString: string | null | undefined,
): string {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
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
