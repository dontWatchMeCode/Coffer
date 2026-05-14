<script setup lang="ts">
import { Head, InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import { ListPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import BookmarkList from '@/components/pages/bookmarks/BookmarkList.vue';
import CreateBookmarkDialog from '@/components/pages/bookmarks/CreateBookmarkDialog.vue';
import DeleteBookmarkDialog from '@/components/pages/bookmarks/DeleteBookmarkDialog.vue';
import { Button } from '@/components/ui/button';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as bookmarksIndex,
    show as showBookmark,
} from '@/routes/team/bookmarks';
import type { BookmarkItem, PaginatedData, Team } from '@/types';

type Props = {
    bookmarks: PaginatedData<BookmarkItem>;
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    bookmarksIndex(currentTeamSlug.value).url,
    'bookmarks',
);

function navigateToBookmark(bookmark: BookmarkItem): void {
    router.visit(
        showBookmark({
            current_team: currentTeamSlug.value,
            bookmark: bookmark.id,
        }).url,
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

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Bookmarks',
                href: bookmarksIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Bookmarks" />

    <PageHeader
        title="Bookmarks"
        description="Track useful links for your team."
    />

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <SearchInput
                    v-model="searchQuery"
                    data-testid="bookmarks-search-input"
                    placeholder="Search bookmarks..."
                />
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-end gap-2">
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

                <InfiniteScroll data="bookmarks">
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

    <DeleteBookmarkDialog ref="deleteDialogRef" />
</template>
