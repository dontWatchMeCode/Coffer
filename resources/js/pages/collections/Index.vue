<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Layers3, ListPlus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateCollectionDialog from '@/components/pages/collections/CreateCollectionDialog.vue';
import DeleteCollectionDialog from '@/components/pages/collections/DeleteCollectionDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useViewMode } from '@/composables/useViewMode';
import { formatDate } from '@/lib/utils';
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

const { viewMode } = useViewMode();

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
    >
        <template #actions>
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
                        data-testid="collections-search-input"
                        placeholder="Search collections..."
                        class="pl-9"
                    />
                </div>
            </div>

            <div v-if="filteredCollections.length > 0" class="space-y-4">
                <div class="flex items-center justify-end">
                    <ViewModeToggle v-model:view-mode="viewMode" />
                </div>

                <ListContainer :layout="viewMode">
                    <ListItem
                        v-for="collection in filteredCollections"
                        :key="collection.id"
                        :aria-label="`Open collection: ${collection.title}`"
                        @click="navigateToCollection(collection)"
                    >
                        <div
                            v-if="viewMode === 'grid'"
                            class="flex flex-col gap-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <ListItemIcon>
                                    <Layers3
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                </ListItemIcon>
                                <ListItemActions>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8"
                                        aria-label="Delete collection"
                                        @click.stop="
                                            deleteDialogRef?.openDeleteDialog(
                                                collection,
                                            )
                                        "
                                    >
                                        <Trash2
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                    </Button>
                                </ListItemActions>
                            </div>

                            <p class="line-clamp-2 text-base font-medium">
                                {{ collection.title }}
                            </p>

                            <p
                                v-if="collection.description"
                                class="line-clamp-4 text-sm text-muted-foreground"
                            >
                                {{ collection.description }}
                            </p>
                            <p
                                v-else
                                class="text-sm text-muted-foreground italic"
                            >
                                No description yet.
                            </p>

                            <div class="mt-auto flex flex-col gap-3">
                                <div
                                    v-if="collection.tags.length"
                                    class="flex flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="tag in collection.tags.slice(
                                            0,
                                            4,
                                        )"
                                        :key="tag.id"
                                        variant="secondary"
                                        class="text-[11px]"
                                    >
                                        {{ tag.name }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Updated
                                    {{ formatDate(collection.updatedAt) }}
                                </p>
                            </div>
                        </div>

                        <div v-else class="flex items-center gap-4">
                            <ListItemIcon>
                                <Layers3
                                    class="h-5 w-5 text-muted-foreground"
                                />
                            </ListItemIcon>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ collection.title }}
                                </p>
                                <p
                                    v-if="collection.description"
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ collection.description }}
                                </p>
                                <p
                                    v-else
                                    class="truncate text-sm text-muted-foreground italic"
                                >
                                    No description yet.
                                </p>
                            </div>

                            <ListItemActions>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    aria-label="Delete collection"
                                    @click.stop="
                                        deleteDialogRef?.openDeleteDialog(
                                            collection,
                                        )
                                    "
                                >
                                    <Trash2
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                </Button>
                            </ListItemActions>
                        </div>
                    </ListItem>
                </ListContainer>
            </div>

            <EmptyState
                v-else
                :title="
                    searchQuery
                        ? 'No collections match your search.'
                        : 'No collections yet.'
                "
                :description="
                    searchQuery
                        ? 'Try another title, description, or tag.'
                        : 'Create a collection to bring related records together.'
                "
                :show-action="!searchQuery"
                action-label="Add your first collection"
                @action="openCreateDialog"
            >
                <template #icon>
                    <Layers3 class="h-12 w-12" />
                </template>
                <template #action-icon>
                    <ListPlus class="mr-1.5 h-3.5 w-3.5" />
                </template>
            </EmptyState>
        </div>
    </div>

    <DeleteCollectionDialog ref="deleteDialogRef" />
</template>
