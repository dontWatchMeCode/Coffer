<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import TaskProgressField from '@/components/pages/tasks/TaskProgressField.vue';
import TaskProjectActions from '@/components/pages/tasks/TaskProjectActions.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { getInitials } from '@/composables/useInitials';
import { getTaskStatusMeta } from '@/lib/tasks';
import type { TaskMember, TaskStatusOption } from '@/types';

type Props = {
    task: {
        id: number;
        title: string;
        status: string;
        progress: number;
        timeEstimate?: number | null;
        assigneeId?: number | null;
        dueAt?: string | null;
        creatorName?: string | null;
    };
    project: {
        id: number;
        name: string;
    };
    members: TaskMember[];
    statuses: TaskStatusOption[];
    projects: { id: number; name: string }[];
    currentTeamSlug: string;
    selectedProjectId?: string;
    showCreatorMeta?: boolean;
    showActions?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showCreatorMeta: true,
    showActions: true,
});

const emit = defineEmits<{
    'update:selectedProjectId': [value: string];
}>();

const unassignedAssigneeValue = 'unassigned';
const selectedAssignee = ref(
    props.task.assigneeId?.toString() ?? unassignedAssigneeValue,
);
const selectedStatus = ref(props.task.status);
const selectedProgress = ref(props.task.progress);
const dueDate = ref(props.task.dueAt ? props.task.dueAt.slice(0, 10) : '');
function formatMinutes(totalMinutes: number): string {
    return `${Math.floor(totalMinutes / 60)}:${String(totalMinutes % 60).padStart(2, '0')}`;
}

const timeEstimateDisplay = ref(
    props.task.timeEstimate ? formatMinutes(props.task.timeEstimate) : '',
);
const selectedProjectId = ref(
    props.selectedProjectId ?? props.project.id.toString(),
);

const taskDeleteDialogOpen = ref(false);

watch(
    () => props.task.status,
    (status) => {
        selectedStatus.value = status;
    },
);

watch(
    () => props.task.progress,
    (progress) => {
        selectedProgress.value = progress;
    },
);

watch(
    () => props.task.assigneeId,
    (assigneeId) => {
        selectedAssignee.value =
            assigneeId?.toString() ?? unassignedAssigneeValue;
    },
);

watch(
    () => props.task.dueAt,
    (dueAt) => {
        dueDate.value = dueAt ? dueAt.slice(0, 10) : '';
    },
);

watch(
    () => props.task.timeEstimate,
    (timeEstimate) => {
        timeEstimateDisplay.value = timeEstimate
            ? formatMinutes(timeEstimate)
            : '';
    },
);

watch(
    () => props.selectedProjectId,
    (value) => {
        if (value) {
            selectedProjectId.value = value;
        }
    },
);

const statusMeta = computed(() => getTaskStatusMeta(selectedStatus.value));
const taskReloadOnly = ['task'];

const assignee = computed(() => {
    if (selectedAssignee.value === unassignedAssigneeValue) {
        return null;
    }

    return props.members.find((member) => {
        return member.id.toString() === selectedAssignee.value;
    });
});

function updateStatus(status: AcceptableValue): void {
    if (typeof status !== 'string') {
        return;
    }

    selectedStatus.value = status;

    router.patch(
        TaskController.update.url({
            current_team: props.currentTeamSlug,
            task: props.task.id,
        }),
        {
            status,
            _return_to_edit: true,
        },
        {
            preserveScroll: true,
            only: taskReloadOnly,
            onError: () => {
                selectedStatus.value = props.task.status;
            },
        },
    );
}

function updateAssignee(assigneeId: AcceptableValue): void {
    if (typeof assigneeId !== 'string') {
        return;
    }

    selectedAssignee.value = assigneeId;

    router.patch(
        TaskController.update.url({
            current_team: props.currentTeamSlug,
            task: props.task.id,
        }),
        {
            _return_to_edit: true,
            assigned_to:
                assigneeId === unassignedAssigneeValue
                    ? null
                    : Number.parseInt(assigneeId, 10),
        },
        {
            preserveScroll: true,
            only: taskReloadOnly,
            onError: () => {
                selectedAssignee.value =
                    props.task.assigneeId?.toString() ??
                    unassignedAssigneeValue;
            },
        },
    );
}

function updateProgress(progress: number): void {
    if (progress === props.task.progress) {
        return;
    }

    router.patch(
        TaskController.update.url({
            current_team: props.currentTeamSlug,
            task: props.task.id,
        }),
        {
            progress,
            _return_to_edit: true,
        },
        {
            preserveScroll: true,
            only: taskReloadOnly,
            onError: () => {
                selectedProgress.value = props.task.progress;
            },
        },
    );
}

function parseTimeEstimate(input: string): number | null {
    const trimmed = input.trim();

    if (!trimmed) {
        return null;
    }

    const colonMatch = trimmed.match(/^(\d+):(\d{1,2})$/);

    if (colonMatch) {
        const minutes = Number(colonMatch[2]);

        if (minutes > 59) {
            return null;
        }

        return Number(colonMatch[1]) * 60 + minutes;
    }

    const hmMatch = trimmed.match(/^(\d+)h(\d{1,2})m?$/i);

    if (hmMatch) {
        const minutes = Number(hmMatch[2]);

        if (minutes > 59) {
            return null;
        }

        return Number(hmMatch[1]) * 60 + minutes;
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

function updateTimeEstimate(): void {
    const parsed = parseTimeEstimate(timeEstimateDisplay.value);

    if (parsed === null && timeEstimateDisplay.value.trim() !== '') {
        timeEstimateDisplay.value = props.task.timeEstimate
            ? formatMinutes(props.task.timeEstimate)
            : '';

        return;
    }

    const value = parsed !== null && parsed > 0 ? parsed : null;

    router.patch(
        TaskController.update.url({
            current_team: props.currentTeamSlug,
            task: props.task.id,
        }),
        {
            time_estimate: value,
            _return_to_edit: true,
        },
        {
            preserveScroll: true,
            only: taskReloadOnly,
            onSuccess: () => {
                timeEstimateDisplay.value = value ? formatMinutes(value) : '';
            },
            onError: () => {
                timeEstimateDisplay.value = props.task.timeEstimate
                    ? formatMinutes(props.task.timeEstimate)
                    : '';
            },
        },
    );
}

function updateDueDate(date: string): void {
    dueDate.value = date;

    router.patch(
        TaskController.update.url({
            current_team: props.currentTeamSlug,
            task: props.task.id,
        }),
        {
            due_at: date || null,
            _return_to_edit: true,
        },
        {
            preserveScroll: true,
            only: taskReloadOnly,
            onError: () => {
                dueDate.value = props.task.dueAt
                    ? props.task.dueAt.slice(0, 10)
                    : '';
            },
        },
    );
}

function updateProject(projectId: AcceptableValue): void {
    if (typeof projectId !== 'string') {
        return;
    }

    const previousProjectId = selectedProjectId.value;
    selectedProjectId.value = projectId;
    emit('update:selectedProjectId', projectId);

    router.patch(
        TaskController.update.url({
            current_team: props.currentTeamSlug,
            task: props.task.id,
        }),
        {
            project_id: Number.parseInt(projectId, 10),
            _return_to_edit: true,
        },
        {
            preserveScroll: true,
            preserveState: false,
            onError: () => {
                selectedProjectId.value = previousProjectId;
                emit('update:selectedProjectId', previousProjectId);
            },
        },
    );
}

function deleteTask(): void {
    router.delete(
        TaskController.destroy.url({
            current_team: props.currentTeamSlug,
            task: props.task.id,
        }),
    );
}
</script>

<template>
    <div class="space-y-4 select-none">
        <div>
            <h3
                class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Assignees
            </h3>
            <Select
                v-model="selectedAssignee"
                @update:model-value="updateAssignee"
            >
                <SelectTrigger variant="pill" size="sm" class="gap-1.5">
                    <SelectValue>
                        <span
                            v-if="assignee"
                            class="pointer-events-none inline-flex items-center gap-1.5"
                        >
                            <Avatar class="h-4 w-4 shrink-0">
                                <AvatarFallback class="text-[9px]">
                                    {{
                                        getInitials(
                                            assignee.name ?? undefined,
                                        ) || '?'
                                    }}
                                </AvatarFallback>
                            </Avatar>
                            {{ assignee.name }}
                        </span>
                        <span
                            v-else
                            class="pointer-events-none text-muted-foreground"
                        >
                            Unassigned
                        </span>
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="unassignedAssigneeValue">
                        Unassigned
                    </SelectItem>
                    <SelectItem
                        v-for="member in members"
                        :key="member.id"
                        :value="member.id.toString()"
                    >
                        <span class="flex items-center gap-2">
                            <Avatar class="h-4 w-4 shrink-0">
                                <AvatarFallback class="text-[9px]">
                                    {{
                                        getInitials(member.name ?? undefined) ||
                                        '?'
                                    }}
                                </AvatarFallback>
                            </Avatar>
                            {{ member.name }}
                        </span>
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <Separator v-if="showCreatorMeta" />

        <div>
            <h3
                class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Status
            </h3>
            <Select v-model="selectedStatus" @update:model-value="updateStatus">
                <SelectTrigger
                    variant="pill"
                    size="sm"
                    class="cursor-pointer gap-1.5"
                >
                    <SelectValue>
                        <span
                            class="pointer-events-none inline-flex items-center gap-1.5"
                        >
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="statusMeta.triggerColor"
                            />
                            {{ statusMeta.label }}
                        </span>
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="statusOption in statuses"
                        :key="statusOption.value"
                        :value="statusOption.value"
                    >
                        <span class="flex items-center gap-2">
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="
                                    getTaskStatusMeta(statusOption.value)
                                        .triggerColor
                                "
                            />
                            {{ statusOption.label }}
                        </span>
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <Separator v-if="showCreatorMeta" />

        <div>
            <h3
                class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Time Estimate
            </h3>
            <Input
                v-model="timeEstimateDisplay"
                placeholder="1:30"
                class="h-8 w-full text-sm"
                @change="updateTimeEstimate"
            />
        </div>

        <Separator v-if="showCreatorMeta" />

        <TaskProgressField
            v-model:selected-progress="selectedProgress"
            @change="updateProgress"
        />

        <Separator v-if="showCreatorMeta || showActions" />

        <div v-if="showCreatorMeta" class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">Created by</span>
                <span>{{ task.creatorName ?? 'Unknown' }}</span>
            </div>
        </div>

        <Separator v-if="showCreatorMeta" />

        <TaskProjectActions
            v-model:task-delete-dialog-open="taskDeleteDialogOpen"
            :selected-project-id="selectedProjectId"
            :projects="projects"
            :due-date="dueDate"
            :show-actions="showActions"
            :task-title="task.title"
            @update-project="updateProject"
            @update-due-date="updateDueDate"
            @delete-task="deleteTask"
        />
    </div>
</template>
