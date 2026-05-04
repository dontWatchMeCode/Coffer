<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import BookmarkList from '@/components/pages/bookmarks/BookmarkList.vue';
import CreateBookmarkDialog from '@/components/pages/bookmarks/CreateBookmarkDialog.vue';
import DeleteBookmarkDialog from '@/components/pages/bookmarks/DeleteBookmarkDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as bookmarksIndex,
    show as showBookmark,
} from '@/routes/team/bookmarks';
import type { BookmarkItem, Team } from '@/types';

type Props = {
    bookmarks: BookmarkItem[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const searchQuery = ref('');

const filteredBookmarks = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.bookmarks;
    }

    const q = searchQuery.value.toLowerCase();

    return props.bookmarks.filter(
        (b) =>
            b.title?.toLowerCase().includes(q) ||
            b.description?.toLowerCase().includes(q) ||
            b.url?.toLowerCase().includes(q),
    );
});

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

const { viewMode } = useViewMode();

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
    >
        <template #actions>
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
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <div class="relative w-full max-w-sm">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        data-testid="bookmarks-search-input"
                        placeholder="Search bookmarks..."
                        class="pl-9"
                    />
                </div>
            </div>

            <div class="space-y-4">
                <div
                    v-if="filteredBookmarks.length > 0"
                    class="flex items-center justify-end"
                >
                    <ViewModeToggle v-model:view-mode="viewMode" />
                </div>

                <BookmarkList
                    :filtered-bookmarks="filteredBookmarks"
                    :search-query="searchQuery"
                    :navigate-to-bookmark="navigateToBookmark"
                    :open-delete-dialog="openDeleteDialog"
                    :open-create-dialog="openCreateDialog"
                    :view-mode="viewMode"
                />
            </div>
        </div>
    </div>

    <DeleteBookmarkDialog ref="deleteDialogRef" />
</template>
