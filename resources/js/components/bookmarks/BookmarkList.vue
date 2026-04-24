<script setup lang="ts">
import {
    Bookmark,
    ExternalLink,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    <div v-if="filteredBookmarks.length > 0" class="space-y-3">
        <div
            v-for="bookmark in filteredBookmarks"
            :key="bookmark.id"
            class="group flex cursor-pointer items-center gap-4 rounded-lg border bg-card p-3 transition-colors hover:bg-accent/50 dark:bg-card/50"
            @click="navigateToBookmark(bookmark)"
        >
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-muted"
            >
                <Bookmark class="h-5 w-5 text-muted-foreground" />
            </div>

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

            <a
                :href="bookmark.url"
                target="_blank"
                rel="noopener noreferrer"
                class="shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
                @click.stop
            >
                <Button variant="ghost" size="icon" class="h-8 w-8">
                    <ExternalLink class="h-4 w-4" />
                </Button>
            </a>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 cursor-pointer opacity-70 transition-opacity group-hover:opacity-100"
                        @click.stop
                    >
                        <MoreHorizontal class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="navigateToBookmark(bookmark)">
                        <Pencil class="mr-2 h-4 w-4" />
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="text-destructive focus:text-destructive"
                        @click="openDeleteDialog(bookmark)"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>

    <div
        v-else
        class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center"
    >
        <Bookmark class="mb-3 h-12 w-12 text-muted-foreground/50" />
        <p class="font-medium">
            {{
                searchQuery
                    ? 'No bookmarks match your search.'
                    : 'No bookmarks yet.'
            }}
        </p>
        <p class="mt-1 max-w-sm text-sm text-muted-foreground">
            {{
                searchQuery
                    ? 'Try a different title, description, or URL.'
                    : 'Create your first team bookmark to start tracking useful links.'
            }}
        </p>
        <Button
            v-if="!searchQuery"
            variant="outline"
            size="sm"
            class="mt-4 cursor-pointer"
            @click="openCreateDialog"
        >
            <Plus class="mr-1.5 h-3.5 w-3.5" />
            Add your first bookmark
        </Button>
    </div>
</template>
