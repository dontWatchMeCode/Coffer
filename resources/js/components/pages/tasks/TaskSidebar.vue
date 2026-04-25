<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import DeleteTaskDialog from '@/components/pages/tasks/DeleteTaskDialog.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    () => props.selectedProjectId,
    (value) => {
        if (value) {
            selectedProjectId.value = value;
        }
    },
);

const statusMeta = computed(() => {
    return getTaskStatusMeta(selectedStatus.value);
});

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
        <!-- Assignees -->
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

        <!-- Status -->
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

        <!-- Progress -->
        <div>
            <h3
                class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Progress
            </h3>
            <div class="mb-3 flex items-center gap-2">
                <div class="group relative flex-1">
                    <div class="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{
                                width: `${selectedProgress}%`,
                            }"
                        />
                    </div>
                    <div
                        class="pointer-events-none absolute inset-0 top-0 flex items-center justify-between opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <div
                            v-for="n in 11"
                            :key="n"
                            class="h-1.5 w-1.5 rounded-full bg-foreground/20"
                        />
                    </div>
                    <input
                        :value="selectedProgress"
                        type="range"
                        min="0"
                        max="100"
                        step="10"
                        class="absolute inset-0 h-2 w-full cursor-pointer appearance-none bg-transparent opacity-0"
                        @input="
                            selectedProgress = Number(
                                ($event.target as HTMLInputElement).value,
                            )
                        "
                        @change="
                            updateProgress(
                                Number(
                                    ($event.target as HTMLInputElement).value,
                                ),
                            )
                        "
                    />
                </div>
                <span class="w-10 text-right text-sm"
                    >{{ selectedProgress }}%</span
                >
            </div>
        </div>

        <Separator v-if="showCreatorMeta || showActions" />

        <!-- Creator -->
        <div v-if="showCreatorMeta" class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">Created by</span>
                <span>{{ task.creatorName ?? 'Unknown' }}</span>
            </div>
        </div>

        <Separator v-if="showCreatorMeta" />

        <!-- Project + schedule -->
        <div class="space-y-3">
            <div class="grid gap-1.5">
                <Label
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >Project</Label
                >
                <Select
                    :model-value="selectedProjectId"
                    @update:model-value="updateProject"
                >
                    <SelectTrigger size="sm" class="h-8 !w-full text-sm">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="proj in projects"
                            :key="proj.id"
                            :value="proj.id.toString()"
                        >
                            {{ proj.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="grid gap-1.5">
                <Label
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >Due date</Label
                >
                <Input
                    type="date"
                    :model-value="dueDate"
                    class="h-8 w-full text-sm"
                    @update:model-value="updateDueDate(String($event))"
                />
            </div>
        </div>

        <Separator v-if="showActions" />

        <!-- Actions -->
        <div v-if="showActions" class="space-y-2">
            <Button
                variant="outline"
                size="sm"
                class="w-full cursor-pointer justify-start gap-2 text-destructive hover:bg-destructive hover:text-destructive-foreground"
                @click="taskDeleteDialogOpen = true"
            >
                Delete task
            </Button>

            <DeleteTaskDialog
                v-model:open="taskDeleteDialogOpen"
                :task-title="task.title"
                @confirm="deleteTask"
            />
        </div>
    </div>
</template>
