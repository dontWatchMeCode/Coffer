<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CollectionDetailOverlay from '@/components/pages/collections/CollectionDetailOverlay.vue';
import CollectionList from '@/components/pages/collections/CollectionList.vue';
import CreateCollectionDialog from '@/components/pages/collections/CreateCollectionDialog.vue';
import DeleteCollectionDialog from '@/components/pages/collections/DeleteCollectionDialog.vue';
import { Button } from '@/components/ui/button';
import { useListDetailOverlay } from '@/composables/useListDetailOverlay';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as collectionsIndex,
    show as showCollection,
    trash as collectionsTrash,
} from '@/routes/team/collections';
import type {
    ActivityHistoryConfig,
    CollectionItem,
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
    collections: PaginatedData<CollectionItem>;
    collection?: CollectionItem;
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
    collectionsIndex(currentTeamSlug.value).url,
    'collections',
);

const { closeDetail, onSavedItem: onSaved } =
    useListDetailOverlay<CollectionItem>(
        'collections',
        currentTeamSlug.value,
        Boolean(props.collections),
        {
            detailItem: () => props.collection,
            lists: [
                {
                    prop: 'collections.data',
                    items: () => props.collections?.data,
                },
            ],
        },
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
        {
            only: [
                'collection',
                'recordLinks',
                'recordTags',
                'activityHistory',
            ],
            preserveScroll: true,
        },
    );
}

function closeCollection(): void {
    closeDetail(collectionsIndex(currentTeamSlug.value).url);
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: {
        currentTeam?: Team | null;
        collection?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Collections',
                href: collectionsIndex(pageProps.currentTeam?.slug).url,
            },
            ...(pageProps.collection
                ? [{ title: pageProps.collection.title }]
                : []),
        ],
    }),
});
</script>

<template>
    <Head :title="props.collection ? props.collection.title : 'Collections'" />

    <div v-if="props.collections && !props.collection">
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

                    <InfiniteScroll data="collections" :buffer="1200">
                        <CollectionList
                            :filtered-collections="props.collections.data"
                            :search-query="searchQuery"
                            :navigate-to-collection="navigateToCollection"
                            :open-delete-dialog="
                                (collection) =>
                                    deleteDialogRef?.openDeleteDialog(
                                        collection,
                                    )
                            "
                            :open-create-dialog="openCreateDialog"
                            :view-mode="viewMode"
                        />
                    </InfiniteScroll>
                </div>
            </div>
        </div>
    </div>

    <CollectionDetailOverlay
        v-if="props.collection"
        :collection="props.collection"
        :record-links="props.recordLinks"
        :record-tags="props.recordTags"
        :activity-history="props.activityHistory"
        @close="closeCollection"
        @saved="onSaved"
    />

    <DeleteCollectionDialog ref="deleteDialogRef" />
</template>
