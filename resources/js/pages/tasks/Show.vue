<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Settings } from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateTaskDialog from '@/components/pages/tasks/CreateTaskDialog.vue';
import ProjectSettingsDialog from '@/components/pages/tasks/ProjectSettingsDialog.vue';
import TaskList from '@/components/pages/tasks/TaskList.vue';
import RecordLinksPanel from '@/components/record-links/RecordLinksPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { index, show, edit } from '@/routes/team/tasks/index';
import type {
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
    tasks: TaskItem[];
    members: TaskMember[];
    statuses: TaskStatusOption[];
    recordLinks?: {
        links: LinkRecord[];
        context: LinkContext;
        endpoints: LinkEndpoints;
    } | null;
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

const showCompletedAndDropped = ref(false);

const visibleTasks = computed(() => {
    if (showCompletedAndDropped.value) {
        return props.tasks;
    }

    return props.tasks.filter(
        (task) => task.status !== 'completed' && task.status !== 'dropped',
    );
});

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

            <ProjectSettingsDialog :project="project">
                <template #trigger>
                    <Button size="icon" title="Project settings">
                        <Settings class="h-4 w-4" />
                    </Button>
                </template>
            </ProjectSettingsDialog>

            <CreateTaskDialog
                :project="project"
                :members="members"
                :statuses="statuses"
            >
                <template #trigger>
                    <Button size="icon" title="Create task">
                        <ListPlus class="h-4 w-4" />
                    </Button>
                </template>
            </CreateTaskDialog>
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
                    <TaskList
                        :visible-tasks="visibleTasks"
                        :project="project"
                        :statuses="statuses"
                        :open-task="openTask"
                        :update-task-status="updateTaskStatus"
                    />
                </div>

                <div
                    class="order-1 h-fit w-full shrink-0 space-y-4 select-none xl:sticky xl:top-4 xl:order-2 xl:w-[280px]"
                >
                    <RecordLinksPanel
                        v-if="recordLinks"
                        :links="recordLinks.links"
                        :context="recordLinks.context"
                        :endpoints="recordLinks.endpoints"
                    />

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
