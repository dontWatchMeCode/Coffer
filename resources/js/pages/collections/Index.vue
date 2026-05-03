<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Layers3, ListPlus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateCollectionDialog from '@/components/pages/collections/CreateCollectionDialog.vue';
import DeleteCollectionDialog from '@/components/pages/collections/DeleteCollectionDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
                        data-testid="collections-search-input"
                        :model-value="searchQuery"
                        placeholder="Search collections..."
                        class="pl-9"
                        @update:model-value="searchQuery = String($event)"
                    />
                </div>
            </div>

            <ListContainer v-if="filteredCollections.length > 0">
                <ListItem
                    v-for="collection in filteredCollections"
                    :key="collection.id"
                    @click="navigateToCollection(collection)"
                >
                    <div class="flex items-start gap-4">
                        <ListItemIcon size="sm" rounded="lg">
                            <Layers3 class="h-4 w-4 text-muted-foreground" />
                        </ListItemIcon>

                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="truncate font-medium">
                                        {{ collection.title }}
                                    </h2>
                                    <p
                                        v-if="collection.description"
                                        class="line-clamp-2 text-sm text-muted-foreground"
                                    >
                                        {{ collection.description }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-sm text-muted-foreground italic"
                                    >
                                        No description yet.
                                    </p>
                                </div>

                                <ListItemActions>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 shrink-0 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        aria-label="Delete collection"
                                        @click.stop="
                                            deleteDialogRef?.openDeleteDialog(
                                                collection,
                                            )
                                        "
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </ListItemActions>
                            </div>

                            <div
                                class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                            >
                                <span
                                    >Updated
                                    {{ formatDate(collection.updatedAt) }}</span
                                >
                                <template v-if="collection.tags.length">
                                    <span>·</span>
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
                                </template>
                            </div>
                        </div>
                    </div>
                </ListItem>
            </ListContainer>

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
