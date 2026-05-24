<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import { forceDestroy, index, restore, trash } from '@/routes/team/collections';
import type { CollectionItem, PaginatedData, Team } from '@/types';

const props = defineProps<{
    collections: PaginatedData<CollectionItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    trash(currentTeamSlug.value).url,
    'collections',
);

const records = computed<TrashRecord[]>(() =>
    props.collections.data.map((collection) => ({
        id: collection.id,
        title: collection.title,
        subtitle: collection.description,
        deletedAt: collection.deletedAt,
    })),
);

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Collections',
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
        title="Deleted Collections"
        description="Restore collections or delete them permanently."
        module-name="Collections"
        scroll-data="collections"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to collections"
        :restore-url="
            (record) =>
                restore({
                    current_team: currentTeamSlug,
                    collection: record.id,
                }).url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({
                    current_team: currentTeamSlug,
                    collection: record.id,
                }).url
        "
    />
</template>
