<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import { forceDestroy, index, restore, trash } from '@/routes/team/files';
import type { FileItem, PaginatedData, Team } from '@/types';

const props = defineProps<{
    files: PaginatedData<FileItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(trash(currentTeamSlug.value).url, 'files');

const records = computed<TrashRecord[]>(() =>
    props.files.data.map((file) => ({
        id: file.id,
        title: file.title,
        subtitle: file.description ?? file.originalName,
        deletedAt: file.deletedAt,
    })),
);

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Files',
                href: index(pageProps.currentTeam?.slug).url,
            },
            {
                title: 'Trash',
                href: trash(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <TrashPage
        v-model:search-query="searchQuery"
        title="Deleted Files"
        description="Restore files or delete them permanently."
        module-name="Files"
        scroll-data="files"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to files"
        :restore-url="
            (record) =>
                restore({ current_team: currentTeamSlug, file: record.id }).url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({ current_team: currentTeamSlug, file: record.id })
                    .url
        "
    />
</template>
