<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import { forceDestroy, index, restore, trash } from '@/routes/team/log';
import type { LogEntryItem, PaginatedData, Team } from '@/types';

const props = defineProps<{
    entries: PaginatedData<LogEntryItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(trash(currentTeamSlug.value).url, 'log');

const records = computed<TrashRecord[]>(() =>
    props.entries.data.map((entry) => ({
        id: entry.id,
        title: entry.body,
        subtitle: entry.category,
        deletedAt: entry.deletedAt,
    })),
);

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            { title: 'Log', href: index(pageProps.currentTeam?.slug).url },
            { title: 'Trash', href: trash(pageProps.currentTeam?.slug).url },
        ],
    }),
});
</script>

<template>
    <TrashPage
        v-model:search-query="searchQuery"
        title="Deleted Log Entries"
        description="Restore log entries or delete them permanently."
        module-name="Log Entries"
        scroll-data="entries"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to log"
        :restore-url="
            (record) =>
                restore({ current_team: currentTeamSlug, logEntry: record.id })
                    .url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({
                    current_team: currentTeamSlug,
                    logEntry: record.id,
                }).url
        "
    />
</template>
