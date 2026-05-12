<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ListPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CollectionList from '@/components/pages/collections/CollectionList.vue';
import CreateCollectionDialog from '@/components/pages/collections/CreateCollectionDialog.vue';
import DeleteCollectionDialog from '@/components/pages/collections/DeleteCollectionDialog.vue';
import { Button } from '@/components/ui/button';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as collectionsIndex,
    show as showCollection,
} from '@/routes/team/collections';
import type { CollectionItem, Team } from '@/types';

type Props = {
    collections: CollectionItem[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const searchQuery = ref('');

const { viewMode } = useViewMode('collections');

const filteredCollections = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return props.collections;
    }

    return props.collections.filter(
        (collection) =>
            collection.title.toLowerCase().includes(query) ||
            collection.description?.toLowerCase().includes(query) ||
            collection.tags.some((tag) =>
                tag.name.toLowerCase().includes(query),
            ),
    );
});

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

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <SearchInput
                    v-model="searchQuery"
                    data-testid="collections-search-input"
                    placeholder="Search collections..."
                />
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-end gap-2">
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
                        v-if="filteredCollections.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <CollectionList
                    :filtered-collections="filteredCollections"
                    :search-query="searchQuery"
                    :navigate-to-collection="navigateToCollection"
                    :open-delete-dialog="
                        (collection) =>
                            deleteDialogRef?.openDeleteDialog(collection)
                    "
                    :open-create-dialog="openCreateDialog"
                    :view-mode="viewMode"
                />
            </div>
        </div>
    </div>

    <DeleteCollectionDialog ref="deleteDialogRef" />
</template>
