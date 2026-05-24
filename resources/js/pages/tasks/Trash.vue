<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import { forceDestroy, index, restore, show, trash } from '@/routes/team/tasks';
import type { PaginatedData, TaskItem, TaskProject } from '@/types';

const props = defineProps<{
    project: Pick<TaskProject, 'id' | 'name' | 'description' | 'isArchived'>;
    tasks: PaginatedData<TaskItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    trash({ current_team: currentTeamSlug.value, project: props.project.id })
        .url,
    'tasks',
);

const records = computed<TrashRecord[]>(() =>
    props.tasks.data.map((task) => ({
        id: task.id,
        title: task.title,
        subtitle: task.description,
        deletedAt: task.deletedAt,
    })),
);

defineOptions({
    inheritAttrs: false,
    layout: (props: {
        currentTeam?: { slug: string } | null;
        project?: { id: number; name: string };
    }) => ({
        breadcrumbs: [
            { title: 'Tasks', href: index(props.currentTeam?.slug).url },
            {
                title: props.project?.name ?? 'Project',
                href: show({
                    current_team: props.currentTeam?.slug,
                    project: props.project?.id ?? 0,
                }).url,
            },
            {
                title: 'Trash',
                href: trash({
                    current_team: props.currentTeam?.slug,
                    project: props.project?.id ?? 0,
                }).url,
            },
        ],
    }),
});
</script>

<template>
    <TrashPage
        v-model:search-query="searchQuery"
        :title="`${project.name} Trash`"
        description="Restore tasks or delete them permanently."
        module-name="Tasks"
        scroll-data="tasks"
        :records="records"
        :back-href="
            show({ current_team: currentTeamSlug, project: project.id }).url
        "
        back-label="Back to project"
        :restore-url="
            (record) =>
                restore({ current_team: currentTeamSlug, task: record.id }).url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({ current_team: currentTeamSlug, task: record.id })
                    .url
        "
    />
</template>
