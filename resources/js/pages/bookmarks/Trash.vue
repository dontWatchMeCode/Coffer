<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import { forceDestroy, index, restore, trash } from '@/routes/team/bookmarks';
import type { BookmarkItem, PaginatedData, Team } from '@/types';

const props = defineProps<{
    bookmarks: PaginatedData<BookmarkItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    trash(currentTeamSlug.value).url,
    'bookmarks',
);

const records = computed<TrashRecord[]>(() =>
    props.bookmarks.data.map((bookmark) => ({
        id: bookmark.id,
        title: bookmark.title,
        subtitle: bookmark.description ?? bookmark.url,
        deletedAt: bookmark.deletedAt,
    })),
);

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Bookmarks',
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
        title="Deleted Bookmarks"
        description="Restore bookmarks or delete them permanently."
        module-name="Bookmarks"
        scroll-data="bookmarks"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to bookmarks"
        :restore-url="
            (record) =>
                restore({ current_team: currentTeamSlug, bookmark: record.id })
                    .url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({
                    current_team: currentTeamSlug,
                    bookmark: record.id,
                }).url
        "
    />
</template>
