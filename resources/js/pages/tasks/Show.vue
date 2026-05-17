<script setup lang="ts">
import { Head, InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Settings } from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import SearchInput from '@/components/list/SearchInput.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateTaskDialog from '@/components/pages/tasks/CreateTaskDialog.vue';
import ProjectSettingsDialog from '@/components/pages/tasks/ProjectSettingsDialog.vue';
import TaskList from '@/components/pages/tasks/TaskList.vue';
import RecordLinksPanel from '@/components/record-links/RecordLinksPanel.vue';
import RecordTagsPanel from '@/components/record-tags/RecordTagsPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useSearch } from '@/composables/useSearch';
import { index, show, edit } from '@/routes/team/tasks/index';
import type {
    ActivityHistoryConfig,
    PaginatedData,
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
    project: Pick<TaskProject, 'id' | 'name' | 'description' | 'isArchived'>;
    tasks: PaginatedData<TaskItem>;
    members: TaskMember[];
    statuses: TaskStatusOption[];
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

const { searchQuery } = useSearch(
    show({
        current_team: currentTeamSlug.value,
        project: props.project.id,
    }).url,
    'tasks',
);

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
        return props.tasks.data;
    }

    return props.tasks.data.filter(
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
        <template v-if="project.isArchived" #actions>
            <Badge variant="secondary"> Archived </Badge>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start">
                <div class="order-2 min-w-0 flex-1 flex-col xl:order-1">
                    <div class="mb-4 flex items-center justify-end">
                        <SearchInput
                            v-model="searchQuery"
                            data-testid="tasks-search-input"
                            placeholder="Search tasks..."
                        />
                    </div>

                    <div
                        v-if="props.tasks.data.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No tasks yet. Create one to get started.
                    </div>

                    <InfiniteScroll v-else data="tasks">
                        <TaskList
                            :visible-tasks="visibleTasks"
                            :project="project"
                            :statuses="statuses"
                            :open-task="openTask"
                            :update-task-status="updateTaskStatus"
                        />
                    </InfiniteScroll>
                </div>

                <div
                    class="order-1 h-fit w-full shrink-0 space-y-4 select-none xl:sticky xl:top-4 xl:order-2 xl:w-[280px]"
                >
                    <div class="flex items-center justify-end gap-2">
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
                    </div>

                    <RecordTagsPanel
                        v-if="recordTags"
                        :tags="recordTags.tags"
                        :context="recordTags.context"
                        :endpoints="recordTags.endpoints"
                    />

                    <RecordLinksPanel
                        v-if="recordLinks"
                        :links="recordLinks.links"
                        :context="recordLinks.context"
                        :endpoints="recordLinks.endpoints"
                    />

                    <ActivityHistoryPanel
                        v-if="activityHistory"
                        :config="activityHistory"
                        :team-slug="currentTeamSlug"
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
