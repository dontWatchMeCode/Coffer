<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CommentsSection from '@/components/pages/tasks/CommentsSection.vue';
import DeleteTaskDialog from '@/components/pages/tasks/DeleteTaskDialog.vue';
import TaskEditForm from '@/components/pages/tasks/TaskEditForm.vue';
import TaskSidebar from '@/components/pages/tasks/TaskSidebar.vue';
import { index, show, edit } from '@/routes/team/tasks/index';
import type {
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

type Props = {
    project: Pick<TaskProject, 'id' | 'name' | 'description' | 'isArchived'>;
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
};

const props = defineProps<Props>();
const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const user = computed(() => page.props.auth.user);
const isEditing = ref(false);
const selectedProjectId = ref(props.project.id.toString());
const deleteDialogOpen = ref(false);

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
            <EditorSidebarLayout
                :created-by="task.creatorName"
                :updated-at="task.updatedAt"
                :on-delete="() => (deleteDialogOpen = true)"
                delete-label="Delete task"
                :record-links="recordLinks"
            >
                <template #main>
                    <TaskEditForm
                        :task="task"
                        :is-editing="isEditing"
                        :update-form-action="
                            TaskController.update.form({
                                current_team: currentTeamSlug,
                                task: task.id,
                            })
                        "
                        @update:is-editing="isEditing = $event"
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
                    <TaskSidebar
                        :task="task"
                        :project="project"
                        :members="members"
                        :statuses="statuses"
                        :projects="projects"
                        :current-team-slug="currentTeamSlug"
                        :selected-project-id="selectedProjectId"
                        :show-creator-meta="false"
                        :show-actions="false"
                        @update:selected-project-id="selectedProjectId = $event"
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
