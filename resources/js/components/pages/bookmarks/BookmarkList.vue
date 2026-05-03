<script setup lang="ts">
import { Bookmark, ExternalLink, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import { Button } from '@/components/ui/button';
import type { BookmarkItem } from '@/types';

type Props = {
    filteredBookmarks: BookmarkItem[];
    searchQuery: string;
    navigateToBookmark: (bookmark: BookmarkItem) => void;
    openDeleteDialog: (bookmark: BookmarkItem) => void;
    openCreateDialog: () => void;
};

defineProps<Props>();
</script>

<template>
    <ListContainer v-if="filteredBookmarks.length > 0">
        <ListItem
            v-for="bookmark in filteredBookmarks"
            :key="bookmark.id"
            @click="navigateToBookmark(bookmark)"
        >
            <div class="flex items-center gap-4">
                <ListItemIcon>
                    <Bookmark class="h-5 w-5 text-muted-foreground" />
                </ListItemIcon>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ bookmark.title }}</p>
                    <p
                        v-if="bookmark.description"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ bookmark.description }}
                    </p>
                    <p v-else class="truncate text-sm text-muted-foreground">
                        {{ bookmark.url }}
                    </p>
                </div>

                <ListItemActions>
                    <a
                        :href="bookmark.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        @click.stop
                    >
                        <Button variant="ghost" size="icon" class="h-8 w-8">
                            <ExternalLink class="h-4 w-4" />
                        </Button>
                    </a>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        aria-label="Edit bookmark"
                        @click.stop="navigateToBookmark(bookmark)"
                    >
                        <Pencil class="h-4 w-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                        aria-label="Delete bookmark"
                        @click.stop="openDeleteDialog(bookmark)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </ListItemActions>
            </div>
        </ListItem>
    </ListContainer>

    <EmptyState
        v-else
        :title="
            searchQuery
                ? 'No bookmarks match your search.'
                : 'No bookmarks yet.'
        "
        :description="
            searchQuery
                ? 'Try a different title, description, or URL.'
                : 'Create your first team bookmark to start tracking useful links.'
        "
        :show-action="!searchQuery"
        action-label="Add your first bookmark"
        @action="openCreateDialog"
    >
        <template #icon>
            <Bookmark class="h-12 w-12" />
        </template>
        <template #action-icon>
            <Plus class="mr-1.5 h-3.5 w-3.5" />
        </template>
    </EmptyState>
</template>
