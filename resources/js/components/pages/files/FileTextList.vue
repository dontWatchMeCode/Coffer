<script setup lang="ts">
import { Download, File, Image, Plus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/components/list/EmptyState.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import { Button } from '@/components/ui/button';
import { formatBytes } from '@/lib/utils';
import type { FileItem } from '@/types';

type Props = {
    files: FileItem[];
    searchQuery: string;
    navigateToFile: (file: FileItem) => void;
    openDeleteDialog: (file: FileItem) => void;
    openCreateDialog: () => void;
};

defineProps<Props>();
</script>

<template>
    <div v-if="files.length > 0" class="space-y-3">
        <ListItem
            v-for="file in files"
            :key="file.id"
            @click="navigateToFile(file)"
        >
            <div class="flex min-w-0 items-center gap-4 overflow-hidden">
                <ListItemIcon>
                    <Image
                        v-if="file.isImage"
                        class="h-5 w-5 text-muted-foreground"
                    />
                    <File v-else class="h-5 w-5 text-muted-foreground" />
                </ListItemIcon>

                <div class="min-w-0 flex-1">
                    <p class="font-medium [overflow-wrap:anywhere]">
                        {{ file.title }}
                    </p>
                    <p
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground"
                    >
                        {{ file.description || file.originalName }}
                    </p>
                </div>

                <p class="hidden text-xs text-muted-foreground sm:block">
                    {{ formatBytes(file.size) }}
                </p>

                <ListItemActions>
                    <Button
                        as="a"
                        :href="file.downloadUrl"
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        aria-label="Download file"
                        @click.stop
                    >
                        <Download class="h-4 w-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        aria-label="Delete file"
                        @click.stop="openDeleteDialog(file)"
                    >
                        <Trash2 class="h-4 w-4 text-muted-foreground" />
                    </Button>
                </ListItemActions>
            </div>
        </ListItem>
    </div>

    <EmptyState
        v-else
        :title="searchQuery ? 'No files match your search.' : 'No files yet.'"
        :description="
            searchQuery
                ? 'Try another title, filename, or description.'
                : 'Upload your first private team file to start building the library.'
        "
        :show-action="!searchQuery"
        action-label="Add your first file"
        @action="openCreateDialog"
    >
        <template #icon>
            <File class="h-12 w-12" />
        </template>
        <template #action-icon>
            <Plus class="mr-1.5 h-3.5 w-3.5" />
        </template>
    </EmptyState>
</template>
