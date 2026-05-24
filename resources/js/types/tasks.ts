import type { ActivityHistoryItem } from './activity-history';
import type { RteBlock } from './notes';

export type TaskStatusOption = {
    value: string;
    label: string;
};

export type TaskStats = {
    projectCount: number;
    activeProjectCount: number;
    openTaskCount: number;
    closedTaskCount: number;
};

export type TaskProject = {
    id: number;
    name: string;
    description?: string | null;
    isArchived: boolean;
    tasksCount: number;
    openTasksCount: number;
    closedTasksCount?: number;
    statusOptions: TaskStatusOption[];
};

export type TaskMember = {
    id: number;
    name: string;
    email: string;
};

export type TaskCommentItem = {
    id: number;
    taskId: number;
    userId: number;
    userName?: string | null;
    blocks: RteBlock[];
    source?: 'user' | 'mcp';
    mcpTokenName?: string | null;
    createdAt?: string | null;
    updatedAt?: string | null;
    activityHistory?: ActivityHistoryItem[];
};

export type TaskItem = {
    id: number;
    projectId: number;
    projectName?: string | null;
    title: string;
    description?: string | null;
    status: string;
    progress: number;
    timeEstimate?: number | null;
    position: number;
    assigneeId?: number | null;
    assigneeName?: string | null;
    creatorId?: number | null;
    creatorName?: string | null;
    updatedAt?: string | null;
    completedAt?: string | null;
    dueAt?: string | null;
    deletedAt?: string | null;
    commentsCount?: number;
};
