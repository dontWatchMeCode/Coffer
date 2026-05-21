<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import { index, trash } from '@/routes/team/calendar';
import { forceDestroy, restore } from '@/routes/team/calendar/events';
import type { CalendarEventItem, PaginatedData, Team } from '@/types';

const props = defineProps<{
    events: PaginatedData<CalendarEventItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(trash(currentTeamSlug.value).url, 'events');

const records = computed<TrashRecord[]>(() =>
    props.events.data.map((event) => ({
        id: event.id,
        title: event.title,
        subtitle: [event.date, event.time].filter(Boolean).join(' '),
        deletedAt: event.deletedAt,
    })),
);

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            { title: 'Calendar', href: index(pageProps.currentTeam?.slug).url },
            { title: 'Trash', href: trash(pageProps.currentTeam?.slug).url },
        ],
    }),
});
</script>

<template>
    <TrashPage
        v-model:search-query="searchQuery"
        title="Deleted Calendar Events"
        description="Restore events or delete them permanently."
        module-name="Calendar Events"
        scroll-data="events"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to calendar"
        :restore-url="
            (record) =>
                restore({ current_team: currentTeamSlug, event: record.id }).url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({
                    current_team: currentTeamSlug,
                    event: record.id,
                }).url
        "
    />
</template>
