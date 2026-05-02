<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Layers3, ListPlus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateCollectionDialog from '@/components/pages/collections/CreateCollectionDialog.vue';
import DeleteCollectionDialog from '@/components/pages/collections/DeleteCollectionDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

function formatDate(value?: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '';
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

            <div
                v-if="filteredCollections.length > 0"
                class="divide-y rounded-lg border"
            >
                <div
                    v-for="collection in filteredCollections"
                    :key="collection.id"
                    class="group cursor-pointer p-4 transition-colors hover:bg-muted/50"
                    @click="navigateToCollection(collection)"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted"
                        >
                            <Layers3 class="h-4 w-4 text-muted-foreground" />
                        </div>

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

                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
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
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center"
            >
                <Layers3 class="mb-3 h-12 w-12 text-muted-foreground/50" />
                <p class="font-medium">
                    {{
                        searchQuery
                            ? 'No collections match your search.'
                            : 'No collections yet.'
                    }}
                </p>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    {{
                        searchQuery
                            ? 'Try another title, description, or tag.'
                            : 'Create a collection to bring related records together.'
                    }}
                </p>
                <Button
                    v-if="!searchQuery"
                    variant="outline"
                    size="sm"
                    class="mt-4 cursor-pointer"
                    @click="openCreateDialog"
                >
                    <ListPlus class="mr-1.5 h-3.5 w-3.5" />
                    Add your first collection
                </Button>
            </div>
        </div>
    </div>

    <DeleteCollectionDialog ref="deleteDialogRef" />
</template>
