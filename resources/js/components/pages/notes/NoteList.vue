<script setup lang="ts">
import { FileText, ListPlus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ViewMode } from '@/composables/useViewMode';
import { formatDate } from '@/lib/utils';
import type { NoteItem } from '@/types';

type Props = {
    filteredNotes: NoteItem[];
    searchQuery: string;
    navigateToNote: (note: NoteItem) => void;
    openDeleteDialog: (note: NoteItem) => void;
    openCreateDialog: () => void;
    viewMode: ViewMode;
};

defineProps<Props>();
</script>

<template>
    <ListContainer v-if="filteredNotes.length > 0" :layout="viewMode">
        <ListItem
            v-for="note in filteredNotes"
            :key="note.id"
            @click="navigateToNote(note)"
        >
            <div v-if="viewMode === 'grid'" class="flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <ListItemIcon>
                        <FileText class="h-5 w-5 text-muted-foreground" />
                    </ListItemIcon>
                    <ListItemActions>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8"
                            aria-label="Delete note"
                            @click.stop="openDeleteDialog(note)"
                        >
                            <Trash2 class="h-4 w-4 text-muted-foreground" />
                        </Button>
                    </ListItemActions>
                </div>

                <p class="line-clamp-2 text-base font-medium">
                    {{ note.title }}
                </p>

                <p
                    v-if="note.excerpt"
                    class="line-clamp-4 text-sm text-muted-foreground"
                >
                    {{ note.excerpt }}
                </p>
                <p v-else class="text-sm text-muted-foreground italic">
                    No body yet.
                </p>

                <div class="mt-auto flex flex-col gap-3">
                    <div v-if="note.tags.length" class="flex flex-wrap gap-1">
                        <Badge
                            v-for="tag in note.tags.slice(0, 4)"
                            :key="tag.id"
                            variant="secondary"
                            class="text-[11px]"
                        >
                            {{ tag.name }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Updated {{ formatDate(note.updatedAt) }}
                    </p>
                </div>
            </div>

            <div v-else class="flex min-w-0 items-center gap-4 overflow-hidden">
                <ListItemIcon>
                    <FileText class="h-5 w-5 text-muted-foreground" />
                </ListItemIcon>

                <div class="min-w-0 flex-1">
                    <p class="font-medium [overflow-wrap:anywhere]">
                        {{ note.title }}
                    </p>
                    <p
                        v-if="note.excerpt"
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground"
                    >
                        {{ note.excerpt }}
                    </p>
                    <p
                        v-else
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground italic"
                    >
                        No body yet.
                    </p>
                </div>

                <ListItemActions>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        aria-label="Delete note"
                        @click.stop="openDeleteDialog(note)"
                    >
                        <Trash2 class="h-4 w-4 text-muted-foreground" />
                    </Button>
                </ListItemActions>
            </div>
        </ListItem>
    </ListContainer>

    <EmptyState
        v-else
        :title="searchQuery ? 'No notes match your search.' : 'No notes yet.'"
        :description="
            searchQuery
                ? 'Try another title, body, or tag.'
                : 'Create your first note to capture team context.'
        "
        :show-action="!searchQuery"
        action-label="Add your first note"
        @action="openCreateDialog"
    >
        <template #icon>
            <FileText class="h-12 w-12" />
        </template>
        <template #action-icon>
            <ListPlus class="mr-1.5 h-3.5 w-3.5" />
        </template>
    </EmptyState>
</template>
