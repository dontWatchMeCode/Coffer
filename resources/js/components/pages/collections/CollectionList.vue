<script setup lang="ts">
import { Layers3, ListPlus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ViewMode } from '@/composables/useViewMode';
import { formatDate } from '@/lib/utils';
import type { CollectionItem } from '@/types';

type Props = {
    filteredCollections: CollectionItem[];
    searchQuery: string;
    navigateToCollection: (collection: CollectionItem) => void;
    openDeleteDialog: (collection: CollectionItem) => void;
    openCreateDialog: () => void;
    viewMode: ViewMode;
};

defineProps<Props>();
</script>

<template>
    <ListContainer v-if="filteredCollections.length > 0" :layout="viewMode">
        <ListItem
            v-for="collection in filteredCollections"
            :key="collection.id"
            :aria-label="`Open collection: ${collection.title}`"
            @click="navigateToCollection(collection)"
        >
            <div v-if="viewMode === 'grid'" class="flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <ListItemIcon>
                        <Layers3 class="h-5 w-5 text-muted-foreground" />
                    </ListItemIcon>
                    <ListItemActions>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8"
                            aria-label="Delete collection"
                            @click.stop="openDeleteDialog(collection)"
                        >
                            <Trash2 class="h-4 w-4 text-muted-foreground" />
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
                <p v-else class="text-sm text-muted-foreground italic">
                    No description yet.
                </p>

                <div class="mt-auto flex flex-col gap-3">
                    <div
                        v-if="collection.tags.length"
                        class="flex flex-wrap gap-1"
                    >
                        <Badge
                            v-for="tag in collection.tags.slice(0, 4)"
                            :key="tag.id"
                            variant="secondary"
                            class="text-[11px]"
                        >
                            {{ tag.name }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Updated {{ formatDate(collection.updatedAt) }}
                    </p>
                </div>
            </div>

            <div v-else class="flex min-w-0 items-center gap-4 overflow-hidden">
                <ListItemIcon>
                    <Layers3 class="h-5 w-5 text-muted-foreground" />
                </ListItemIcon>

                <div class="min-w-0 flex-1">
                    <p class="font-medium [overflow-wrap:anywhere]">
                        {{ collection.title }}
                    </p>
                    <p
                        v-if="collection.description"
                        class="text-sm text-muted-foreground [overflow-wrap:anywhere]"
                    >
                        {{ collection.description }}
                    </p>
                    <p
                        v-else
                        class="text-sm text-muted-foreground italic [overflow-wrap:anywhere]"
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
                        @click.stop="openDeleteDialog(collection)"
                    >
                        <Trash2 class="h-4 w-4 text-muted-foreground" />
                    </Button>
                </ListItemActions>
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
</template>
