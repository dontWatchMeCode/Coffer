<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/form/InputError.vue';
import TaskProgressField from '@/components/pages/tasks/TaskProgressField.vue';
import RichTextEditor from '@/components/richtext/RichTextEditor.vue';
import { trimStoredRichText } from '@/components/richtext/storage';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getInitials } from '@/composables/useInitials';
import {
    formatTimeEstimate,
    formatTimeEstimateInput,
    getTaskStatusMeta,
    parseTimeEstimate,
} from '@/lib/tasks';
import type { TaskMember, TaskStatusOption } from '@/types';

type Props = {
    task: {
        id: number;
        title: string;
        description?: string | null;
        status: string;
        progress: number;
        timeEstimate?: number | null;
        assigneeId?: number | null;
        assigneeName?: string | null;
        dueAt?: string | null;
    };
    project: {
        id: number;
        name: string;
    };
    members: TaskMember[];
    statuses: TaskStatusOption[];
    projects: { id: number; name: string }[];
    selectedProjectId: string;
    isEditing: boolean;
    updateFormAction: Record<string, string>;
};

type FormMethods = {
    submit: () => void;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:isEditing': [value: boolean];
    'update:selectedProjectId': [value: string];
    processing: [value: boolean];
    editSuccess: [];
}>();

const taskFormRef = ref<FormMethods | null>(null);
const unassignedAssigneeValue = 'unassigned';
const descriptionBody = ref(props.task.description ?? '');
const selectedAssignee = ref(
    props.task.assigneeId?.toString() ?? unassignedAssigneeValue,
);
const selectedStatus = ref(props.task.status);
const selectedProgress = ref(props.task.progress);
const selectedProjectId = ref(props.selectedProjectId);
const dueDate = ref(props.task.dueAt ? props.task.dueAt.slice(0, 10) : '');
const timeEstimateDisplay = ref(
    formatTimeEstimateInput(props.task.timeEstimate),
);

const statusLabel = computed(() => {
    return (
        props.statuses.find((status) => status.value === props.task.status)
            ?.label ?? props.task.status
    );
});

const formattedTimeEstimate = computed(() => {
    return formatTimeEstimate(props.task.timeEstimate) || 'No estimate';
});

const formattedDueDate = computed(() => {
    if (!props.task.dueAt) {
        return 'No due date';
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(`${props.task.dueAt.slice(0, 10)}T00:00:00`));
});

const detailRows = computed(() => [
    ['Assignees', props.task.assigneeName ?? 'Unassigned'],
    ['Status', statusLabel.value],
    ['Time Estimate', formattedTimeEstimate.value],
    ['Progress', `${props.task.progress}%`],
    ['Project', props.project.name],
    ['Due Date', formattedDueDate.value],
]);

const parsedTimeEstimate = computed(() => {
    const parsed = parseTimeEstimate(timeEstimateDisplay.value);

    return parsed !== null && parsed > 0 ? parsed.toString() : '';
});

const assignee = computed(() => {
    if (selectedAssignee.value === unassignedAssigneeValue) {
        return null;
    }

    return props.members.find((member) => {
        return member.id.toString() === selectedAssignee.value;
    });
});

const selectedStatusMeta = computed(() =>
    getTaskStatusMeta(selectedStatus.value),
);

const statusOptionMeta = computed(() => {
    return new Map(
        props.statuses.map((status) => [
            status.value,
            getTaskStatusMeta(status.value),
        ]),
    );
});

watch(
    () => props.task.description,
    (description) => {
        descriptionBody.value = description ?? '';
    },
);

watch(
    () =>
        [
            props.task.assigneeId,
            props.task.status,
            props.task.progress,
            props.task.dueAt,
            props.task.timeEstimate,
        ] as const,
    () => resetDetailFields(),
);

function resetDetailFields(): void {
    selectedAssignee.value =
        props.task.assigneeId?.toString() ?? unassignedAssigneeValue;
    selectedStatus.value = props.task.status;
    selectedProgress.value = props.task.progress;
    selectedProjectId.value = props.selectedProjectId;
    dueDate.value = props.task.dueAt ? props.task.dueAt.slice(0, 10) : '';
    timeEstimateDisplay.value = formatTimeEstimateInput(
        props.task.timeEstimate,
    );
}

function updateSelectedProject(projectId: AcceptableValue): void {
    if (typeof projectId !== 'string') {
        return;
    }

    selectedProjectId.value = projectId;
    emit('update:selectedProjectId', projectId);
}

function normalizeTimeEstimate(): void {
    const parsed = parseTimeEstimate(timeEstimateDisplay.value);

    if (parsed === null && timeEstimateDisplay.value.trim() !== '') {
        timeEstimateDisplay.value = formatTimeEstimateInput(
            props.task.timeEstimate,
        );

        return;
    }

    timeEstimateDisplay.value =
        parsed !== null ? formatTimeEstimateInput(parsed) : '';
}

function cancelEditing(): void {
    descriptionBody.value = props.task.description ?? '';
    resetDetailFields();
    selectedProjectId.value = props.project.id.toString();
    emit('update:selectedProjectId', props.project.id.toString());
    emit('update:isEditing', false);
}

function handleEditSuccess(): void {
    emit('editSuccess');
}

defineExpose({
    submit: () => taskFormRef.value?.submit(),
    cancel: cancelEditing,
});
</script>

<template>
    <div v-if="!isEditing" class="space-y-4">
        <div class="rounded-lg border bg-card p-4 shadow-sm">
            <RichTextEditor
                v-if="task.description"
                :model-value="task.description"
                :editable="false"
                :on-activate="() => emit('update:isEditing', true)"
            />
            <div v-else class="text-muted-foreground italic">
                No description provided.
            </div>
        </div>

        <div class="rounded-lg border bg-card p-4 shadow-sm">
            <div class="grid gap-4 md:grid-cols-2">
                <div v-for="[label, value] in detailRows" :key="label">
                    <h3
                        class="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ label }}
                    </h3>
                    <p class="text-sm">{{ value }}</p>
                </div>
            </div>
        </div>
    </div>

    <Form
        v-else
        ref="taskFormRef"
        v-bind="updateFormAction"
        class="space-y-4"
        v-slot="{ errors, processing }"
        @start="emit('processing', true)"
        @success="handleEditSuccess"
        @finish="emit('processing', false)"
    >
        <input name="_return_to_edit" type="hidden" value="1" />

        <div class="space-y-4">
            <div class="grid gap-2">
                <Label>Title</Label>
                <Input name="title" :default-value="task.title" required />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label class="mb-1">Description</Label>
                <input
                    name="description"
                    type="hidden"
                    :value="trimStoredRichText(descriptionBody)"
                />
                <RichTextEditor
                    :model-value="descriptionBody"
                    :editable="true"
                    placeholder="Add a description..."
                    @update:model-value="(v) => (descriptionBody = v)"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-2">
                <Label class="mb-1">Details</Label>
                <div class="rounded-lg border bg-card p-4 shadow-sm">
                    <input
                        name="assigned_to"
                        type="hidden"
                        :value="
                            selectedAssignee === unassignedAssigneeValue
                                ? ''
                                : selectedAssignee
                        "
                    />
                    <input
                        name="status"
                        type="hidden"
                        :value="selectedStatus"
                    />
                    <input
                        name="time_estimate"
                        type="hidden"
                        :value="parsedTimeEstimate"
                    />
                    <input
                        name="progress"
                        type="hidden"
                        :value="selectedProgress"
                    />
                    <input
                        name="project_id"
                        type="hidden"
                        :value="selectedProjectId"
                    />
                    <input name="due_at" type="hidden" :value="dueDate" />

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label
                                class="mb-2 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Assignees
                            </Label>
                            <Select v-model="selectedAssignee">
                                <SelectTrigger
                                    variant="pill"
                                    size="sm"
                                    class="gap-1.5"
                                >
                                    <SelectValue>
                                        <span
                                            v-if="assignee"
                                            class="pointer-events-none inline-flex items-center gap-1.5"
                                        >
                                            <Avatar class="h-4 w-4 shrink-0">
                                                <AvatarFallback
                                                    class="text-[9px]"
                                                >
                                                    {{
                                                        getInitials(
                                                            assignee.name ??
                                                                undefined,
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
                                    <SelectItem
                                        :value="unassignedAssigneeValue"
                                    >
                                        Unassigned
                                    </SelectItem>
                                    <SelectItem
                                        v-for="member in members"
                                        :key="member.id"
                                        :value="member.id.toString()"
                                    >
                                        <span class="flex items-center gap-2">
                                            <Avatar class="h-4 w-4 shrink-0">
                                                <AvatarFallback
                                                    class="text-[9px]"
                                                >
                                                    {{
                                                        getInitials(
                                                            member.name ??
                                                                undefined,
                                                        ) || '?'
                                                    }}
                                                </AvatarFallback>
                                            </Avatar>
                                            {{ member.name }}
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label
                                class="mb-2 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Status
                            </Label>
                            <Select v-model="selectedStatus">
                                <SelectTrigger
                                    variant="pill"
                                    size="sm"
                                    class="gap-1.5"
                                >
                                    <SelectValue>
                                        <span
                                            class="pointer-events-none inline-flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full"
                                                :class="
                                                    selectedStatusMeta.triggerColor
                                                "
                                            />
                                            {{ selectedStatusMeta.label }}
                                        </span>
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="status in statuses"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        <span class="flex items-center gap-2">
                                            <span
                                                class="h-2 w-2 rounded-full"
                                                :class="
                                                    statusOptionMeta.get(
                                                        status.value,
                                                    )?.triggerColor
                                                "
                                            />
                                            {{ status.label }}
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label
                                class="mb-2 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Time Estimate
                            </Label>
                            <Input
                                v-model="timeEstimateDisplay"
                                placeholder="1:30"
                                class="h-8 w-full text-sm"
                                @change="normalizeTimeEstimate"
                            />
                        </div>

                        <div>
                            <Label
                                class="mb-2 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Due Date
                            </Label>
                            <Input
                                v-model="dueDate"
                                type="date"
                                class="h-8 w-full text-sm"
                            />
                        </div>

                        <div>
                            <Label
                                class="mb-2 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Project
                            </Label>
                            <Select
                                :model-value="selectedProjectId"
                                @update:model-value="updateSelectedProject"
                            >
                                <SelectTrigger
                                    size="sm"
                                    class="h-8 !w-full text-sm"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="availableProject in projects"
                                        :key="availableProject.id"
                                        :value="availableProject.id.toString()"
                                    >
                                        {{ availableProject.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <TaskProgressField
                                v-model:selected-progress="selectedProgress"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="hidden" type="submit" :disabled="processing" />
    </Form>
</template>
