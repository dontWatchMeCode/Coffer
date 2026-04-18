<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import PageHeader from '@/components/PageHeader.vue';
import CommentsSection from '@/components/tasks/CommentsSection.vue';
import TaskEditForm from '@/components/tasks/TaskEditForm.vue';
import TaskSidebar from '@/components/tasks/TaskSidebar.vue';
import { index, show, edit } from '@/routes/team/tasks/index';
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
    projects: { id: number; name: string }[];
};

const props = defineProps<Props>();
const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const user = computed(() => page.props.auth.user);
const isEditing = ref(false);
const selectedProjectId = ref(props.project.id.toString());

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
                </div>

                <!-- Sidebar -->
                <TaskSidebar
                    :task="task"
                    :project="project"
                    :members="members"
                    :statuses="statuses"
                    :projects="projects"
                    :current-team-slug="currentTeamSlug"
                    :selected-project-id="selectedProjectId"
                    @update:selected-project-id="selectedProjectId = $event"
                />
            </div>
        </div>
    </div>
</template>
