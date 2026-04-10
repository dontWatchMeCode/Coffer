export type TaskStatusOption = {
    value: string;
    label: string;
};

export type TaskStats = {
    projectCount: number;
    activeProjectCount: number;
    openTaskCount: number;
    completedTaskCount: number;
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
    completedAt?: string | null;
    commentsCount?: number;
};
