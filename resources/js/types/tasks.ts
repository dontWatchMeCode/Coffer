import type { ActivityHistoryItem } from './activity-history';

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
    body: string;
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
    position: number;
    assigneeId?: number | null;
    assigneeName?: string | null;
    creatorId?: number | null;
    creatorName?: string | null;
    updatedAt?: string | null;
    completedAt?: string | null;
    dueAt?: string | null;
    commentsCount?: number;
};
