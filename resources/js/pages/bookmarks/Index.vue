<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import BookmarkDetailOverlay from '@/components/pages/bookmarks/BookmarkDetailOverlay.vue';
import BookmarkList from '@/components/pages/bookmarks/BookmarkList.vue';
import CreateBookmarkDialog from '@/components/pages/bookmarks/CreateBookmarkDialog.vue';
import DeleteBookmarkDialog from '@/components/pages/bookmarks/DeleteBookmarkDialog.vue';
import { Button } from '@/components/ui/button';
import { useListDetailOverlay } from '@/composables/useListDetailOverlay';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as bookmarksIndex,
    show as showBookmark,
    trash as bookmarksTrash,
} from '@/routes/team/bookmarks';
import type {
    ActivityHistoryConfig,
    BookmarkItem,
    PaginatedData,
    Team,
} from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    bookmarks: PaginatedData<BookmarkItem>;
    bookmark?: BookmarkItem;
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
    bookmarksIndex(currentTeamSlug.value).url,
    'bookmarks',
);

const { closeDetail, onSavedItem: onSaved } =
    useListDetailOverlay<BookmarkItem>(
        'bookmarks',
        currentTeamSlug.value,
        Boolean(props.bookmarks),
        {
            detailItem: () => props.bookmark,
            lists: [
                { prop: 'bookmarks.data', items: () => props.bookmarks?.data },
            ],
        },
    );

function navigateToBookmark(bookmark: BookmarkItem): void {
    router.visit(
        showBookmark({
            current_team: currentTeamSlug.value,
            bookmark: bookmark.id,
        }).url,
        {
            only: ['bookmark', 'recordLinks', 'recordTags', 'activityHistory'],
            preserveScroll: true,
        },
    );
}

const createDialogRef = ref<InstanceType<typeof CreateBookmarkDialog> | null>(
    null,
);
const deleteDialogRef = ref<InstanceType<typeof DeleteBookmarkDialog> | null>(
    null,
);

function openCreateDialog(): void {
    if (createDialogRef.value) {
        createDialogRef.value.createDialogOpen = true;
    }
}

function openDeleteDialog(bookmark: BookmarkItem): void {
    deleteDialogRef.value?.openDeleteDialog(bookmark);
}

const { viewMode } = useViewMode('bookmarks');

function closeBookmark(): void {
    closeDetail(bookmarksIndex(currentTeamSlug.value).url);
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: {
        currentTeam?: Team | null;
        bookmark?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Bookmarks',
                href: bookmarksIndex(pageProps.currentTeam?.slug).url,
            },
            ...(pageProps.bookmark
                ? [{ title: pageProps.bookmark.title }]
                : []),
        ],
    }),
});
</script>

<template>
    <Head :title="props.bookmark ? props.bookmark.title : 'Bookmarks'" />

    <div v-if="props.bookmarks && !props.bookmark">
        <PageHeader
            title="Bookmarks"
            description="Track useful links for your team."
        />

        <div class="min-w-0 flex-1 px-4 py-6">
            <div class="mx-auto w-full max-w-7xl">
                <div class="mb-4 flex items-center justify-end gap-3">
                    <SearchInput
                        v-model="searchQuery"
                        data-testid="bookmarks-search-input"
                        placeholder="Search bookmarks..."
                    />
                </div>

                <div class="min-w-0 space-y-4">
                    <div class="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            title="Trash"
                            as-child
                        >
                            <Link :href="bookmarksTrash(currentTeamSlug).url">
                                <Trash2 class="h-4 w-4" />
                            </Link>
                        </Button>

                        <CreateBookmarkDialog ref="createDialogRef">
                            <template #trigger>
                                <Button
                                    size="icon"
                                    title="Create bookmark"
                                    class="cursor-pointer"
                                >
                                    <ListPlus class="h-4 w-4" />
                                </Button>
                            </template>
                        </CreateBookmarkDialog>

                        <ViewModeToggle
                            v-if="props.bookmarks.data.length > 0"
                            v-model:view-mode="viewMode"
                        />
                    </div>

                    <InfiniteScroll data="bookmarks" :buffer="1200">
                        <BookmarkList
                            :filtered-bookmarks="props.bookmarks.data"
                            :search-query="searchQuery"
                            :navigate-to-bookmark="navigateToBookmark"
                            :open-delete-dialog="openDeleteDialog"
                            :open-create-dialog="openCreateDialog"
                            :view-mode="viewMode"
                        />
                    </InfiniteScroll>
                </div>
            </div>
        </div>
    </div>

    <BookmarkDetailOverlay
        v-if="props.bookmark"
        :bookmark="props.bookmark"
        :record-links="props.recordLinks"
        :record-tags="props.recordTags"
        :activity-history="props.activityHistory"
        @close="closeBookmark"
        @saved="onSaved"
    />

    <DeleteBookmarkDialog ref="deleteDialogRef" />
</template>
