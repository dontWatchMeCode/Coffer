<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import { forceDestroy, index, restore, trash } from '@/routes/team/notes';
import type { NoteItem, PaginatedData, Team } from '@/types';

const props = defineProps<{
    notes: PaginatedData<NoteItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(trash(currentTeamSlug.value).url, 'notes');

const records = computed<TrashRecord[]>(() =>
    props.notes.data.map((note) => ({
        id: note.id,
        title: note.title,
        subtitle: note.excerpt,
        deletedAt: note.deletedAt,
    })),
);

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            { title: 'Notes', href: index(pageProps.currentTeam?.slug).url },
            { title: 'Trash', href: trash(pageProps.currentTeam?.slug).url },
        ],
    }),
});
</script>

<template>
    <TrashPage
        v-model:search-query="searchQuery"
        title="Deleted Notes"
        description="Restore notes or delete them permanently."
        module-name="Notes"
        scroll-data="notes"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to notes"
        :restore-url="
            (record) =>
                restore({ current_team: currentTeamSlug, note: record.id }).url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({ current_team: currentTeamSlug, note: record.id })
                    .url
        "
    />
</template>
