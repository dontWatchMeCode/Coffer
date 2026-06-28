<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CommentsSection from '@/components/pages/tasks/CommentsSection.vue';
import DeleteTaskDialog from '@/components/pages/tasks/DeleteTaskDialog.vue';
import TaskEditForm from '@/components/pages/tasks/TaskEditForm.vue';
import { useCopyAsMarkdown } from '@/composables/useCopyAsMarkdown';
import { useListDetailOverlay } from '@/composables/useListDetailOverlay';
import { serializeTask } from '@/lib/markdown-serializers';
import { index, show, edit } from '@/routes/team/tasks/index';
import type {
    ActivityHistoryConfig,
    TaskCommentItem,
    TaskItem,
    TaskMember,
    TaskProject,
    TaskStatusOption,
} from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    project: Pick<
        TaskProject,
        'id' | 'name' | 'description' | 'isArchived' | 'statusOptions'
    >;
    task: TaskItem;
    comments: TaskCommentItem[];
    members: TaskMember[];
    statuses: TaskStatusOption[];
    projects: { id: number; name: string }[];
    recordLinks?: {
        links: LinkRecord[];
        context: LinkContext;
        endpoints: LinkEndpoints;
    } | null;
    recordTags?: {
        tags: RecordTag[];
        context: TagContext;
        endpoints: TagEndpoints;
    } | null;
    activityHistory?: ActivityHistoryConfig;
};

const props = defineProps<Props>();
const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const user = computed(() => page.props.auth.user);
const isEditing = ref(
    Boolean(new URLSearchParams(window.location.search).get('edit')),
);

if (isEditing.value) {
    const url = new URL(window.location.href);
    url.searchParams.delete('edit');
    window.history.replaceState(window.history.state, '', url.toString());
}

const selectedProjectId = ref(props.project.id.toString());
const deleteDialogOpen = ref(false);
const taskEditFormRef = ref<InstanceType<typeof TaskEditForm> | null>(null);
const isSubmitting = ref(false);

const { closeDetail } = useListDetailOverlay(
    'tasks-ticket',
    currentTeamSlug.value,
    false,
);

defineOptions({
    inheritAttrs: false,
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

function handleEditSuccess(): void {
    const newProjectId = Number.parseInt(selectedProjectId.value, 10);

    if (newProjectId !== props.project.id) {
        router.visit(
            edit({
                current_team: currentTeamSlug.value,
                project: newProjectId,
                task: props.task.id,
            }).url,
            { preserveScroll: true, preserveState: false },
        );

        return;
    }

    isEditing.value = false;
}

function confirmDelete(): void {
    deleteDialogOpen.value = false;

    router.delete(
        TaskController.destroy.url({
            current_team: currentTeamSlug.value,
            task: props.task.id,
        }),
    );
}

const { copied, copyError, copyAsMarkdown } = useCopyAsMarkdown();

function handleCopyAsMarkdown(): void {
    copyAsMarkdown(
        serializeTask(
            props.task,
            props.project,
            props.comments,
            props.recordTags?.tags ?? [],
            props.recordLinks?.links ?? [],
        ),
    );
}

function closeTask(): void {
    closeDetail(
        show({
            current_team: currentTeamSlug.value,
            project: props.project.id,
        }).url,
    );
}
</script>

<template>
    <Head :title="`${task.title} #${task.id}`" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="task.title"
            :subtitle="`#${task.id}`"
            :back-handler="closeTask"
            back-label="Back to project"
        />

        <!-- Content -->
        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :created-by="task.creatorName"
                :updated-at="task.updatedAt"
                :on-edit="isEditing ? null : () => (isEditing = true)"
                :on-save="isEditing ? () => taskEditFormRef?.submit() : null"
                :save-disabled="isSubmitting"
                :on-cancel="isEditing ? () => taskEditFormRef?.cancel() : null"
                :cancel-disabled="isSubmitting"
                :on-delete="() => (deleteDialogOpen = true)"
                delete-label="Delete task"
                :delete-disabled="isSubmitting"
                :on-copy-as-markdown="handleCopyAsMarkdown"
                :copy-as-markdown-copied="copied"
                :copy-as-markdown-error="copyError"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #main>
                    <TaskEditForm
                        ref="taskEditFormRef"
                        :task="task"
                        :project="project"
                        :members="members"
                        :statuses="statuses"
                        :projects="projects"
                        :selected-project-id="selectedProjectId"
                        :is-editing="isEditing"
                        :update-form-action="
                            TaskController.update.form({
                                current_team: currentTeamSlug,
                                task: task.id,
                            })
                        "
                        @update:is-editing="isEditing = $event"
                        @update:selected-project-id="selectedProjectId = $event"
                        @processing="isSubmitting = $event"
                        @edit-success="handleEditSuccess"
                    />

                    <CommentsSection
                        :comments="comments"
                        :task-id="task.id"
                        :current-team-slug="currentTeamSlug"
                        :user-id="user.id"
                    />
                </template>

                <template #sidebar-top>
                    <ActivityHistoryPanel
                        v-if="activityHistory"
                        :config="activityHistory"
                        :team-slug="currentTeamSlug"
                    />
                </template>
            </EditorSidebarLayout>
        </div>

        <DeleteTaskDialog
            v-model:open="deleteDialogOpen"
            :task-title="task.title"
            @confirm="confirmDelete"
        />
    </div>
</template>
