<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Ban,
    Check,
    CircleHelp,
    Flag,
    ListPlus,
    MessageCircle,
    Settings,
    Trash2,
} from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/Tasks/ProjectController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    getTaskStatusMeta,
    formatRelativeTime,
    taskInputLikeClass,
} from '@/lib/tasks';
import { index, show, edit } from '@/routes/team/tasks/index';
import type {
    TaskItem,
    TaskMember,
    TaskProject,
    TaskStatusOption,
} from '@/types';

type Props = {
    project: Pick<TaskProject, 'id' | 'name' | 'description' | 'isArchived'>;
    projects: TaskProject[];
    tasks: TaskItem[];
    members: TaskMember[];
    statuses: TaskStatusOption[];
};

const props = defineProps<Props>();
const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

defineOptions({
    layout: (props: {
        currentTeam?: { slug: string } | null;
        project?: { id: number; name: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Tasks',
                href: index(props.currentTeam?.slug),
            },
            {
                title: props.project?.name ?? 'Project',
                href: show({
                    current_team: props.currentTeam?.slug,
                    project: props.project?.id ?? 0,
                }),
            },
        ],
    }),
});

const projectSettingsOpen = ref(false);
const createTaskOpen = ref(false);
const createTaskFormKey = ref(0);
const projectSettingsFormKey = ref(0);
const showCompletedAndDropped = ref(false);

const visibleTasks = computed(() => {
    if (showCompletedAndDropped.value) {
        return props.tasks;
    }

    return props.tasks.filter(
        (task) => task.status !== 'completed' && task.status !== 'dropped',
    );
});

const statusIcons = {
    flag: Flag,
    activity: Activity,
    ban: Ban,
    check: Check,
    trash: Trash2,
    help: CircleHelp,
};

function openTask(task: TaskItem): void {
    router.visit(
        edit({
            current_team: currentTeamSlug.value,
            project: props.project.id,
            task: task.id,
        }),
    );
}

function updateTaskStatus(task: TaskItem, status: AcceptableValue): void {
    if (typeof status !== 'string') {
        return;
    }

    router.patch(
        TaskController.update.url({
            current_team: currentTeamSlug.value,
            task: task.id,
        }),
        { status },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="project.name" />

    <PageHeader
        :title="project.name"
        description="Review this project, create tasks, and update work status."
        :back-href="index(currentTeamSlug).url"
        back-label="Back to projects"
    >
        <template #actions>
            <Badge v-if="project.isArchived" variant="secondary">
                Archived
            </Badge>

            <Dialog v-model:open="projectSettingsOpen">
                <DialogTrigger as-child>
                    <Button
                        size="icon"
                        title="Project settings"
                        class="cursor-pointer"
                    >
                        <Settings class="h-4 w-4" />
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        :key="projectSettingsFormKey"
                        v-bind="
                            ProjectController.update.form({
                                current_team: currentTeamSlug,
                                project: project.id,
                            })
                        "
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                        @success="projectSettingsOpen = false"
                    >
                        <DialogHeader>
                            <DialogTitle>Project settings</DialogTitle>
                            <DialogDescription>
                                Update the selected project.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="selected-project-name">Name</Label>
                            <Input
                                id="selected-project-name"
                                name="name"
                                :default-value="project.name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="selected-project-description"
                                >Description</Label
                            >
                            <textarea
                                id="selected-project-description"
                                name="description"
                                :class="taskInputLikeClass"
                                rows="4"
                                :value="project.description ?? ''"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox
                                name="archived"
                                :default-checked="project.isArchived"
                            />
                            <label class="text-sm">Archived</label>
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="processing"
                                >Save project</Button
                            >
                        </div>
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="createTaskOpen">
                <DialogTrigger as-child>
                    <Button
                        size="icon"
                        title="Create task"
                        class="cursor-pointer"
                    >
                        <ListPlus class="h-4 w-4" />
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        :key="createTaskFormKey"
                        v-bind="TaskController.store.form(currentTeamSlug)"
                        reset-on-success
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                        @success="createTaskOpen = false"
                    >
                        <DialogHeader>
                            <DialogTitle>Create task</DialogTitle>
                            <DialogDescription>
                                Add a new task to {{ project.name }}.
                            </DialogDescription>
                        </DialogHeader>

                        <input
                            name="project_id"
                            type="hidden"
                            :value="project.id"
                        />
                        <input name="position" type="hidden" value="0" />

                        <div class="grid gap-2">
                            <Label for="task-title">Title</Label>
                            <Input
                                id="task-title"
                                name="title"
                                placeholder="Ship project overview page"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="task-description">Description</Label>
                            <textarea
                                id="task-description"
                                name="description"
                                :class="taskInputLikeClass"
                                rows="4"
                                placeholder="Add enough detail for the next person to pick this up."
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="task-status">Status</Label>
                                <select
                                    id="task-status"
                                    name="status"
                                    :class="taskInputLikeClass"
                                    required
                                >
                                    <option
                                        v-for="status in props.statuses"
                                        :key="status.value"
                                        :value="status.value"
                                        :selected="status.value === 'planned'"
                                    >
                                        {{ status.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.status" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="task-progress">Progress</Label>
                                <Input
                                    id="task-progress"
                                    name="progress"
                                    type="number"
                                    min="0"
                                    max="100"
                                    default-value="0"
                                    required
                                />
                                <InputError :message="errors.progress" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="task-assignee">Assignee</Label>
                            <select
                                id="task-assignee"
                                name="assigned_to"
                                :class="taskInputLikeClass"
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="member in members"
                                    :key="member.id"
                                    :value="member.id"
                                >
                                    {{ member.name }}
                                </option>
                            </select>
                            <InputError :message="errors.assigned_to" />
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="processing"
                                >Create task</Button
                            >
                        </div>
                    </Form>
                </DialogContent>
            </Dialog>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div
                v-if="props.tasks.length === 0"
                class="py-8 text-center text-sm text-muted-foreground"
            >
                No tasks yet. Create one to get started.
            </div>

            <div v-else class="flex flex-col gap-6 xl:flex-row xl:items-start">
                <div class="order-2 min-w-0 flex-1 flex-col xl:order-1">
                    <div
                        v-if="visibleTasks.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No open tasks. Enable "Show completed & dropped" to see
                        archived tasks.
                    </div>

                    <TooltipProvider :delay-duration="300">
                        <div
                            v-for="task in visibleTasks"
                            :key="task.id"
                            class="group relative mx-2 flex cursor-pointer items-center gap-3 rounded-2xl px-3 py-2 transition-colors hover:bg-muted/30"
                            role="link"
                            :tabindex="0"
                            @click="openTask(task)"
                            @keydown.enter.space.prevent="openTask(task)"
                        >
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Select
                                        :model-value="task.status"
                                        @update:model-value="
                                            (value) =>
                                                updateTaskStatus(task, value)
                                        "
                                    >
                                        <SelectTrigger
                                            class="cursor-pointer border-0"
                                            hide-icon
                                            variant="pill"
                                            @click.stop
                                        >
                                            <component
                                                :is="
                                                    statusIcons[
                                                        getTaskStatusMeta(
                                                            task.status,
                                                        )
                                                            .icon as keyof typeof statusIcons
                                                    ]
                                                "
                                                class="h-5 w-5"
                                                :class="
                                                    getTaskStatusMeta(
                                                        task.status,
                                                    ).badgeColor
                                                "
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="statusOption in props.statuses"
                                                :key="statusOption.value"
                                                :value="statusOption.value"
                                            >
                                                <span
                                                    class="flex items-center gap-2"
                                                >
                                                    <component
                                                        :is="
                                                            statusIcons[
                                                                getTaskStatusMeta(
                                                                    statusOption.value,
                                                                )
                                                                    .icon as keyof typeof statusIcons
                                                            ]
                                                        "
                                                        class="h-4 w-4"
                                                        :class="
                                                            getTaskStatusMeta(
                                                                statusOption.value,
                                                            ).badgeColor
                                                        "
                                                    />
                                                    {{ statusOption.label }}
                                                </span>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>
                                        {{
                                            getTaskStatusMeta(task.status).label
                                        }}
                                    </p>
                                </TooltipContent>
                            </Tooltip>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="truncate text-sm font-medium"
                                        >{{ task.title }}</span
                                    >
                                </div>
                                <div
                                    class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground"
                                >
                                    <span
                                        >{{ project.name }} #{{ task.id }}</span
                                    >
                                    <span class="text-border">•</span>
                                    <span v-if="task.creatorName">
                                        by {{ task.creatorName }}
                                    </span>
                                    <span
                                        v-if="task.completedAt"
                                        class="text-border"
                                        >•</span
                                    >
                                    <span v-if="task.completedAt">
                                        {{
                                            formatRelativeTime(task.completedAt)
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                <div
                                    class="h-1.5 w-16 overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary transition-all"
                                        :style="{
                                            width: `${task.progress}%`,
                                        }"
                                    />
                                </div>
                                <span
                                    class="w-8 text-right text-sm text-muted-foreground tabular-nums"
                                >
                                    {{ task.progress }}%
                                </span>
                            </div>

                            <div
                                v-if="
                                    task.commentsCount !== undefined &&
                                    task.commentsCount > 0
                                "
                                class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground"
                            >
                                <MessageCircle class="h-3.5 w-3.5" />
                                <span>{{ task.commentsCount }}</span>
                            </div>
                        </div>
                    </TooltipProvider>
                </div>

                <div
                    class="order-1 h-fit w-full shrink-0 space-y-4 select-none xl:sticky xl:top-4 xl:order-2 xl:w-[280px]"
                >
                    <div>
                        <h3
                            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Filters
                        </h3>
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show-completed"
                                v-model="showCompletedAndDropped"
                            />
                            <label
                                for="show-completed"
                                class="cursor-pointer text-sm text-muted-foreground"
                            >
                                Show completed & dropped
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
