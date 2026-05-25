<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CollectionList from '@/components/pages/collections/CollectionList.vue';
import CreateCollectionDialog from '@/components/pages/collections/CreateCollectionDialog.vue';
import DeleteCollectionDialog from '@/components/pages/collections/DeleteCollectionDialog.vue';
import { Button } from '@/components/ui/button';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as collectionsIndex,
    show as showCollection,
    trash as collectionsTrash,
} from '@/routes/team/collections';
import type { CollectionItem, PaginatedData, Team } from '@/types';

type Props = {
    collections: PaginatedData<CollectionItem>;
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const { searchQuery } = useSearch(
    collectionsIndex(currentTeamSlug.value).url,
    'collections',
);

const { viewMode } = useViewMode('collections');

const createDialogRef = ref<InstanceType<typeof CreateCollectionDialog> | null>(
    null,
);
const deleteDialogRef = ref<InstanceType<typeof DeleteCollectionDialog> | null>(
    null,
);

function openCreateDialog(): void {
    if (createDialogRef.value) {
        createDialogRef.value.createDialogOpen = true;
    }
}

function navigateToCollection(collection: CollectionItem): void {
    router.visit(
        showCollection({
            current_team: currentTeamSlug.value,
            collection: collection.id,
        }).url,
    );
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Collections',
                href: collectionsIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Collections" />

    <PageHeader
        title="Collections"
        description="Group linked records into browsable collections."
    />

    <div class="min-w-0 flex-1 px-4 py-6">
        <div class="mx-auto w-full max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <SearchInput
                    v-model="searchQuery"
                    data-testid="collections-search-input"
                    placeholder="Search collections..."
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
                        <Link :href="collectionsTrash(currentTeamSlug).url">
                            <Trash2 class="h-4 w-4" />
                        </Link>
                    </Button>

                    <CreateCollectionDialog ref="createDialogRef">
                        <template #trigger>
                            <Button
                                size="icon"
                                title="Create collection"
                                class="cursor-pointer"
                            >
                                <ListPlus class="h-4 w-4" />
                            </Button>
                        </template>
                    </CreateCollectionDialog>

                    <ViewModeToggle
                        v-if="props.collections.data.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <InfiniteScroll data="collections">
                    <CollectionList
                        :filtered-collections="props.collections.data"
                        :search-query="searchQuery"
                        :navigate-to-collection="navigateToCollection"
                        :open-delete-dialog="
                            (collection) =>
                                deleteDialogRef?.openDeleteDialog(collection)
                        "
                        :open-create-dialog="openCreateDialog"
                        :view-mode="viewMode"
                    />
                </InfiniteScroll>
            </div>
        </div>
    </div>

    <DeleteCollectionDialog ref="deleteDialogRef" />
</template>
