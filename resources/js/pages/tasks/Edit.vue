<script setup lang="ts">
import { Form, Head, useForm, usePage, router } from '@inertiajs/vue3';
import {
    Check,
    MessageCircle,
    Pencil,
    Plus,
    Send,
    Trash2,
    X,
} from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import {
    destroy as destroyTaskComment,
    store as storeTaskComment,
    update as updateTaskComment,
} from '@/actions/App/Http/Controllers/Tasks/TaskCommentController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
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
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { getInitials } from '@/composables/useInitials';
import {
    formatExactDateTime,
    formatRelativeTime,
    getTaskStatusMeta,
    taskInputLikeClass,
} from '@/lib/tasks';
import { index, show } from '@/routes/team/tasks/index';
import type {
    TaskCommentItem,
    TaskItem,
    TaskMember,
    TaskProject,
    TaskStatusOption,
} from '@/types';

type Props = {
    project: Pick<TaskProject, 'id' | 'name' | 'description' | 'isArchived'>;
    task: TaskItem;
    comments: TaskCommentItem[];
    members: TaskMember[];
    statuses: TaskStatusOption[];
};

const props = defineProps<Props>();
const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const user = computed(() => page.props.auth.user);
const isEditing = ref(false);
const selectedStatus = ref(props.task.status);
const selectedProgress = ref(props.task.progress);
const unassignedAssigneeValue = 'unassigned';
const selectedAssignee = ref(
    props.task.assigneeId?.toString() ?? unassignedAssigneeValue,
);
const commentForm = useForm(`TaskComment:${props.task.id}`, {
    body: '',
});
const isCreatingComment = ref(false);
const editingCommentId = ref<number | null>(null);
const commentPendingDeletion = ref<TaskCommentItem | null>(null);
const taskDeleteDialogOpen = ref(false);
const editCommentForm = useForm(`EditTaskComment:${props.task.id}`, {
    body: '',
});

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

const statusMeta = computed(() => {
    return getTaskStatusMeta(selectedStatus.value);
});

const assignee = computed(() => {
    if (selectedAssignee.value === unassignedAssigneeValue) {
        return null;
    }

    return props.members.find((member) => {
        return member.id.toString() === selectedAssignee.value;
    });
});

defineOptions({
    layout: (layoutProps: {
        currentTeam?: { slug: string } | null;
        project?: { id: number; name: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Tasks',
                href: index(layoutProps.currentTeam?.slug),
            },
            {
                title: layoutProps.project?.name ?? 'Project',
                href: show({
                    current_team: layoutProps.currentTeam?.slug,
                    project: layoutProps.project?.id ?? 0,
                }),
            },
            {
                title: 'Edit',
            },
        ],
    }),
});

function updateStatus(status: AcceptableValue): void {
    if (typeof status !== 'string') {
        return;
    }

    selectedStatus.value = status;

    router.patch(
        TaskController.update.url({
            current_team: currentTeamSlug.value,
            task: props.task.id,
        }),
        {
            status,
            _return_to_edit: true,
        },
        {
            preserveScroll: true,
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
            current_team: currentTeamSlug.value,
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
            current_team: currentTeamSlug.value,
            task: props.task.id,
        }),
        {
            progress,
            _return_to_edit: true,
        },
        {
            preserveScroll: true,
            onError: () => {
                selectedProgress.value = props.task.progress;
            },
        },
    );
}

function submitComment(): void {
    commentForm.submit(
        storeTaskComment({
            current_team: currentTeamSlug.value,
            task: props.task.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                commentForm.reset();
                isCreatingComment.value = false;
            },
        },
    );
}

function startCreatingComment(): void {
    isCreatingComment.value = true;
    commentForm.clearErrors();
}

function cancelCreatingComment(): void {
    isCreatingComment.value = false;
    commentForm.reset();
    commentForm.clearErrors();
}

function startEditingComment(comment: TaskCommentItem): void {
    editingCommentId.value = comment.id;
    editCommentForm.body = comment.body;
    editCommentForm.clearErrors();
}

function cancelEditingComment(): void {
    editingCommentId.value = null;
    editCommentForm.reset();
    editCommentForm.clearErrors();
}

function updateComment(comment: TaskCommentItem): void {
    editCommentForm.submit(
        updateTaskComment({
            current_team: currentTeamSlug.value,
            task: props.task.id,
            comment: comment.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditingComment();
            },
        },
    );
}

function deleteComment(comment: TaskCommentItem): void {
    router.delete(
        destroyTaskComment({
            current_team: currentTeamSlug.value,
            task: props.task.id,
            comment: comment.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                commentPendingDeletion.value = null;

                if (editingCommentId.value === comment.id) {
                    cancelEditingComment();
                }
            },
        },
    );
}

function openDeleteCommentDialog(comment: TaskCommentItem): void {
    commentPendingDeletion.value = comment;
}

function closeDeleteCommentDialog(): void {
    commentPendingDeletion.value = null;
}

function deleteTask(): void {
    router.delete(
        TaskController.destroy.url({
            current_team: currentTeamSlug.value,
            task: props.task.id,
        }),
    );
}
</script>

<template>
    <Head :title="`${task.title} #${task.id}`" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="task.title"
            :subtitle="`#${task.id}`"
            :back-href="
                show({
                    current_team: currentTeamSlug,
                    project: project.id,
                }).url
            "
            back-label="Back to project"
        />

        <!-- Content -->
        <div class="flex-1 px-4 py-6">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 xl:flex-row">
                <!-- Main content -->
                <div class="order-2 min-w-0 flex-1 space-y-6 xl:order-1">
                    <!-- Description -->
                    <div v-if="!isEditing" class="space-y-4">
                        <div class="rounded-lg border bg-card p-4 shadow-sm">
                            <div
                                v-if="task.description"
                                class="prose prose-sm max-w-none"
                            >
                                <p class="whitespace-pre-wrap">
                                    {{ task.description }}
                                </p>
                            </div>
                            <div v-else class="text-muted-foreground italic">
                                No description provided.
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="isEditing = true"
                            >
                                Edit
                            </Button>
                        </div>
                    </div>

                    <!-- Edit form -->
                    <Form
                        v-else
                        v-bind="
                            TaskController.update.form({
                                current_team: currentTeamSlug,
                                task: task.id,
                            })
                        "
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                        @success="isEditing = false"
                    >
                        <input
                            name="project_id"
                            type="hidden"
                            :value="project.id"
                        />
                        <input name="_return_to_edit" type="hidden" value="1" />

                        <div class="rounded-lg border bg-card p-4">
                            <div class="space-y-4">
                                <div class="grid gap-2">
                                    <Label>Title</Label>
                                    <Input
                                        name="title"
                                        :default-value="task.title"
                                        required
                                    />
                                    <InputError :message="errors.title" />
                                </div>

                                <div class="grid gap-2">
                                    <Label>Description</Label>
                                    <textarea
                                        name="description"
                                        :class="taskInputLikeClass"
                                        rows="6"
                                        :value="task.description ?? ''"
                                        placeholder="Add a description..."
                                    />
                                    <InputError :message="errors.description" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                type="button"
                                @click="isEditing = false"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="processing">
                                Update task
                            </Button>
                        </div>
                    </Form>

                    <section class="space-y-4">
                        <div class="flex min-h-10 items-center gap-3">
                            <MessageCircle
                                class="h-4 w-4 text-muted-foreground"
                            />
                            <h2 class="text-sm font-semibold">Comments</h2>
                            <Badge variant="secondary">
                                {{ comments.length }}
                            </Badge>
                            <div class="flex-1" />
                            <div class="flex min-w-[140px] justify-end">
                                <Button
                                    v-if="!isCreatingComment"
                                    type="button"
                                    variant="outline"
                                    class="gap-2"
                                    @click="startCreatingComment"
                                >
                                    <Plus class="h-4 w-4" />
                                    Add comment
                                </Button>
                            </div>
                        </div>

                        <form
                            v-if="isCreatingComment"
                            class="space-y-3 rounded-xl border bg-background/70 p-4"
                            @submit.prevent="submitComment"
                        >
                            <div class="grid gap-2">
                                <Label for="task-comment-body"
                                    >Add comment</Label
                                >
                                <textarea
                                    id="task-comment-body"
                                    v-model="commentForm.body"
                                    :class="taskInputLikeClass"
                                    rows="4"
                                    placeholder="Add context, decisions, or blockers..."
                                />
                                <InputError
                                    :message="commentForm.errors.body"
                                />
                            </div>

                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="gap-2"
                                    @click="cancelCreatingComment"
                                >
                                    <X class="h-4 w-4" />
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="commentForm.processing"
                                    class="gap-2"
                                >
                                    <Send class="h-4 w-4" />
                                    Add comment
                                </Button>
                            </div>
                        </form>

                        <div
                            v-if="comments.length === 0"
                            class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground"
                        >
                            No comments yet. Add one to capture backend notes,
                            decisions, or follow-ups.
                        </div>

                        <div v-else class="space-y-3">
                            <article
                                v-for="comment in comments"
                                :key="comment.id"
                                class="rounded-xl border bg-background/70 p-4"
                            >
                                <div
                                    :class="[
                                        'flex items-start gap-3',
                                        editingCommentId === comment.id
                                            ? ''
                                            : 'mb-3',
                                    ]"
                                >
                                    <Avatar class="h-9 w-9 shrink-0">
                                        <AvatarFallback>
                                            {{
                                                getInitials(
                                                    comment.userName ??
                                                        undefined,
                                                ) || '?'
                                            }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-2"
                                        >
                                            <div
                                                class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                            >
                                                <span
                                                    class="text-sm font-medium"
                                                >
                                                    {{
                                                        comment.userName ??
                                                        'Unknown'
                                                    }}
                                                </span>
                                                <TooltipProvider
                                                    :delay-duration="150"
                                                >
                                                    <Tooltip>
                                                        <TooltipTrigger
                                                            as-child
                                                        >
                                                            <span
                                                                class="text-xs text-muted-foreground"
                                                            >
                                                                {{
                                                                    formatRelativeTime(
                                                                        comment.createdAt,
                                                                    )
                                                                }}
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            {{
                                                                formatExactDateTime(
                                                                    comment.createdAt,
                                                                )
                                                            }}
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                            </div>
                                            <TooltipProvider
                                                v-if="
                                                    comment.userId === user.id
                                                "
                                                :delay-duration="150"
                                            >
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    <template
                                                        v-if="
                                                            editingCommentId ===
                                                            comment.id
                                                        "
                                                    >
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                as-child
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    class="h-8 w-8 p-0"
                                                                    @click="
                                                                        cancelEditingComment
                                                                    "
                                                                >
                                                                    <X
                                                                        class="h-3.5 w-3.5"
                                                                    />
                                                                    <span
                                                                        class="sr-only"
                                                                        >Cancel
                                                                        edit</span
                                                                    >
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Cancel
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                as-child
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    class="h-8 w-8 p-0"
                                                                    :disabled="
                                                                        editCommentForm.processing
                                                                    "
                                                                    @click="
                                                                        updateComment(
                                                                            comment,
                                                                        )
                                                                    "
                                                                >
                                                                    <Check
                                                                        class="h-3.5 w-3.5"
                                                                    />
                                                                    <span
                                                                        class="sr-only"
                                                                        >Save
                                                                        comment</span
                                                                    >
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Save
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </template>
                                                    <template v-else>
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                as-child
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    class="h-8 w-8 p-0"
                                                                    @click="
                                                                        startEditingComment(
                                                                            comment,
                                                                        )
                                                                    "
                                                                >
                                                                    <Pencil
                                                                        class="h-3.5 w-3.5"
                                                                    />
                                                                    <span
                                                                        class="sr-only"
                                                                        >Edit
                                                                        comment</span
                                                                    >
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Edit comment
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                as-child
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                                    @click="
                                                                        openDeleteCommentDialog(
                                                                            comment,
                                                                        )
                                                                    "
                                                                >
                                                                    <Trash2
                                                                        class="h-3.5 w-3.5"
                                                                    />
                                                                    <span
                                                                        class="sr-only"
                                                                        >Delete
                                                                        comment</span
                                                                    >
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Delete comment
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </template>
                                                </div>
                                            </TooltipProvider>
                                        </div>

                                        <form
                                            v-if="
                                                editingCommentId === comment.id
                                            "
                                            class="mt-3"
                                            @submit.prevent="
                                                updateComment(comment)
                                            "
                                        >
                                            <textarea
                                                v-model="editCommentForm.body"
                                                :class="taskInputLikeClass"
                                                rows="4"
                                            />
                                            <InputError
                                                v-if="
                                                    editCommentForm.errors.body
                                                "
                                                class="mt-3"
                                                :message="
                                                    editCommentForm.errors.body
                                                "
                                            />
                                        </form>
                                        <p
                                            v-else
                                            class="mt-2 text-sm leading-6 whitespace-pre-wrap text-foreground/90"
                                        >
                                            {{ comment.body }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <Dialog
                            :open="commentPendingDeletion !== null"
                            @update:open="
                                (open) => {
                                    if (!open) {
                                        closeDeleteCommentDialog();
                                    }
                                }
                            "
                        >
                            <DialogContent>
                                <DialogHeader class="space-y-3">
                                    <DialogTitle>Delete comment?</DialogTitle>
                                    <DialogDescription>
                                        This will permanently remove this
                                        comment from the task activity.
                                    </DialogDescription>
                                </DialogHeader>

                                <div
                                    v-if="commentPendingDeletion"
                                    class="rounded-lg border bg-muted/40 p-3 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ commentPendingDeletion.body }}
                                </div>

                                <DialogFooter class="gap-2">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        @click="closeDeleteCommentDialog"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        v-if="commentPendingDeletion"
                                        type="button"
                                        variant="destructive"
                                        class="gap-2"
                                        @click="
                                            deleteComment(
                                                commentPendingDeletion,
                                            )
                                        "
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete comment
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </section>
                </div>

                <!-- Sidebar -->
                <div
                    class="order-1 h-fit w-full shrink-0 space-y-4 select-none xl:order-2 xl:w-[280px]"
                >
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
                                            <AvatarFallback class="text-[9px]">
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

                    <Separator />

                    <!-- Status -->
                    <div>
                        <h3
                            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Status
                        </h3>
                        <Select
                            v-model="selectedStatus"
                            @update:model-value="updateStatus"
                        >
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
                                                getTaskStatusMeta(
                                                    statusOption.value,
                                                ).triggerColor
                                            "
                                        />
                                        {{ statusOption.label }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <Separator />

                    <!-- Progress -->
                    <div>
                        <h3
                            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Progress
                        </h3>
                        <div class="mb-3 flex items-center gap-2">
                            <div class="group relative flex-1">
                                <div
                                    class="h-2 overflow-hidden rounded-full bg-muted"
                                >
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
                                            ($event.target as HTMLInputElement)
                                                .value,
                                        )
                                    "
                                    @change="
                                        updateProgress(
                                            Number(
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
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

                    <Separator />

                    <!-- Metadata -->
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground"
                                >Created by</span
                            >
                            <span>{{ task.creatorName ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Project</span>
                            <span>{{ project.name }}</span>
                        </div>
                    </div>

                    <Separator />

                    <!-- Actions -->
                    <div class="space-y-2">
                        <Dialog v-model:open="taskDeleteDialogOpen">
                            <DialogTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="w-full cursor-pointer justify-start gap-2 text-destructive hover:bg-destructive hover:text-destructive-foreground"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    Delete task
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader class="space-y-3">
                                    <DialogTitle>Delete task?</DialogTitle>
                                    <DialogDescription>
                                        This will permanently remove this task
                                        and its comments.
                                    </DialogDescription>
                                </DialogHeader>

                                <div
                                    class="rounded-lg border bg-muted/40 p-3 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ task.title }}
                                </div>

                                <DialogFooter class="gap-2">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        @click="taskDeleteDialogOpen = false"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        class="gap-2"
                                        @click="deleteTask"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete task
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
