<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import {
    forceDestroy,
    index,
    restore,
    trash,
} from '@/routes/team/spreadsheets';
import type { PaginatedData, SpreadsheetWorkbook, Team } from '@/types';

const props = defineProps<{
    spreadsheets: PaginatedData<SpreadsheetWorkbook>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const { searchQuery } = useSearch(
    trash(currentTeamSlug.value).url,
    'spreadsheets',
);

const records = computed<TrashRecord[]>(() =>
    props.spreadsheets.data.map((spreadsheet) => ({
        id: spreadsheet.id,
        title: spreadsheet.title,
        subtitle: `${spreadsheet.rowCount} rows`,
        deletedAt: spreadsheet.deletedAt,
    })),
);

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Spreadsheets',
                href: index(pageProps.currentTeam?.slug).url,
            },
            { title: 'Trash', href: trash(pageProps.currentTeam?.slug).url },
        ],
    }),
});
</script>

<template>
    <TrashPage
        v-model:search-query="searchQuery"
        title="Deleted Spreadsheets"
        description="Restore spreadsheets or delete them permanently."
        module-name="Spreadsheets"
        scroll-data="spreadsheets"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to spreadsheets"
        :restore-url="
            (record) =>
                restore({
                    current_team: currentTeamSlug,
                    spreadsheet: record.id,
                }).url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({
                    current_team: currentTeamSlug,
                    spreadsheet: record.id,
                }).url
        "
    />
</template>
