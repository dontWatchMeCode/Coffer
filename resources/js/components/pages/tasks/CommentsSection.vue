<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Check,
    Bot,
    MessageCircle,
    Pencil,
    Plus,
    Send,
    Trash2,
    X,
} from 'lucide-vue-next';
import { ref } from 'vue';
import {
    destroy as destroyTaskComment,
    store as storeTaskComment,
    update as updateTaskComment,
} from '@/actions/App/Http/Controllers/Tasks/TaskCommentController';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import BlockEditor from '@/components/blocks/BlockEditor.vue';
import InputError from '@/components/form/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useRelativeTime } from '@/composables/useRelativeTime';
import { formatExactDateTime, formatRelativeTime } from '@/lib/tasks';
import type { TaskCommentItem } from '@/types';
import type { RteBlock } from '@/types/notes';

type Props = {
    comments: TaskCommentItem[];
    taskId: number;
    currentTeamSlug: string;
    userId: number;
};

const props = defineProps<Props>();

const { now } = useRelativeTime();
let nextTemporaryBlockId = -1;
const emptyCommentBlocks = (): RteBlock[] => [
    {
        id: nextTemporaryBlockId--,
        type: 'text',
        position: 0,
        payload: { content: '' },
    },
];

const commentBlocks = ref<RteBlock[]>(emptyCommentBlocks());
const editCommentBlocks = ref<RteBlock[]>(emptyCommentBlocks());
const commentErrors = ref<Record<string, string>>({});
const editCommentErrors = ref<Record<string, string>>({});
const isSubmittingComment = ref(false);
const isUpdatingComment = ref(false);

const isCreatingComment = ref(false);
const editingCommentId = ref<number | null>(null);
const commentPendingDeletion = ref<TaskCommentItem | null>(null);

function startCreatingComment(): void {
    isCreatingComment.value = true;
    commentBlocks.value = emptyCommentBlocks();
    commentErrors.value = {};
}

function cancelCreatingComment(): void {
    isCreatingComment.value = false;
    commentBlocks.value = emptyCommentBlocks();
    commentErrors.value = {};
}

function submitComment(): void {
    isSubmittingComment.value = true;

    router.post(
        storeTaskComment({
            current_team: props.currentTeamSlug,
            task: props.taskId,
        }).url,
        { blocks: commentBlocks.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                commentBlocks.value = emptyCommentBlocks();
                isCreatingComment.value = false;
            },
            onError: (errors) => (commentErrors.value = errors),
            onFinish: () => (isSubmittingComment.value = false),
        },
    );
}

function startEditingComment(comment: TaskCommentItem): void {
    editingCommentId.value = comment.id;
    editCommentBlocks.value = JSON.parse(JSON.stringify(comment.blocks));
    editCommentErrors.value = {};
}

function cancelEditingComment(): void {
    editingCommentId.value = null;
    editCommentBlocks.value = emptyCommentBlocks();
    editCommentErrors.value = {};
}

function updateComment(comment: TaskCommentItem): void {
    isUpdatingComment.value = true;

    router.patch(
        updateTaskComment({
            current_team: props.currentTeamSlug,
            task: props.taskId,
            comment: comment.id,
        }).url,
        { blocks: editCommentBlocks.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditingComment();
            },
            onError: (errors) => (editCommentErrors.value = errors),
            onFinish: () => (isUpdatingComment.value = false),
        },
    );
}

function openDeleteCommentDialog(comment: TaskCommentItem): void {
    commentPendingDeletion.value = comment;
}

function closeDeleteCommentDialog(): void {
    commentPendingDeletion.value = null;
}

function deleteComment(comment: TaskCommentItem): void {
    router.delete(
        destroyTaskComment({
            current_team: props.currentTeamSlug,
            task: props.taskId,
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
</script>

<template>
    <section class="space-y-4">
        <div class="flex min-h-10 items-center gap-3">
            <MessageCircle class="h-4 w-4 text-muted-foreground" />
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
            <BlockEditor v-model:blocks="commentBlocks" />
            <InputError :message="commentErrors.blocks" />

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
                    :disabled="isSubmittingComment"
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
            No comments yet. Add one to capture backend notes, decisions, or
            follow-ups.
        </div>

        <div v-else class="space-y-3">
            <article
                v-for="comment in comments"
                :key="comment.id"
                class="rounded-xl border bg-background/70 p-4"
            >
                <div class="flex items-start">
                    <div class="min-w-0 flex-1">
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <div
                                class="flex flex-wrap items-center gap-x-2 gap-y-1"
                            >
                                <span class="text-sm font-medium">
                                    {{ comment.userName ?? 'Unknown' }}
                                </span>
                                <Badge
                                    v-if="comment.source === 'mcp'"
                                    variant="outline"
                                    class="gap-1 rounded-full px-2 py-0 text-[0.6875rem] font-medium text-muted-foreground"
                                >
                                    <Bot class="h-3 w-3" />
                                    MCP<span v-if="comment.mcpTokenName"
                                        >: {{ comment.mcpTokenName }}</span
                                    >
                                </Badge>
                                <TooltipProvider :delay-duration="150">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <span
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    formatRelativeTime(
                                                        comment.createdAt,
                                                        now,
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
                            <div class="flex items-start gap-3">
                                <ActivityHistoryPanel
                                    v-if="
                                        comment.activityHistory &&
                                        comment.activityHistory.length > 0
                                    "
                                    :activities="comment.activityHistory"
                                    variant="compact"
                                />
                                <TooltipProvider
                                    v-if="comment.userId === userId"
                                    :delay-duration="150"
                                >
                                    <div class="flex items-start gap-3">
                                        <template
                                            v-if="
                                                editingCommentId === comment.id
                                            "
                                        >
                                            <Tooltip>
                                                <TooltipTrigger as-child>
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
                                                        <span class="sr-only"
                                                            >Cancel edit</span
                                                        >
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Cancel
                                                </TooltipContent>
                                            </Tooltip>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        class="h-8 w-8 p-0"
                                                        :disabled="
                                                            isUpdatingComment
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
                                                        <span class="sr-only"
                                                            >Save comment</span
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
                                                <TooltipTrigger as-child>
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
                                                        <span class="sr-only"
                                                            >Edit comment</span
                                                        >
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Edit comment
                                                </TooltipContent>
                                            </Tooltip>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
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
                                                        <span class="sr-only"
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
                        </div>

                        <form
                            v-if="editingCommentId === comment.id"
                            class="mt-3"
                            @submit.prevent="updateComment(comment)"
                        >
                            <BlockEditor
                                v-model:blocks="editCommentBlocks"
                                :editable="true"
                            />
                            <InputError
                                v-if="editCommentErrors.blocks"
                                class="mt-3"
                                :message="editCommentErrors.blocks"
                            />
                        </form>
                        <BlockEditor
                            v-else
                            class="mt-3"
                            :blocks="comment.blocks"
                            :editable="false"
                        />
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
                        This will permanently remove this comment from the task
                        activity.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="commentPendingDeletion"
                    class="rounded-lg border bg-muted/40 p-3"
                >
                    <BlockEditor
                        :blocks="commentPendingDeletion.blocks"
                        :editable="false"
                    />
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
                        @click="deleteComment(commentPendingDeletion)"
                    >
                        <Trash2 class="h-4 w-4" />
                        Delete comment
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>
