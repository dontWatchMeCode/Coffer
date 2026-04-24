<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { destroy as deleteBookmark } from '@/routes/team/bookmarks';
import type { BookmarkItem } from '@/types';

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const deleteDialogOpen = ref(false);
const deletingBookmark = ref<BookmarkItem | null>(null);

function openDeleteDialog(bookmark: BookmarkItem): void {
    deletingBookmark.value = bookmark;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!deletingBookmark.value) {
        return;
    }

    const bookmark = deletingBookmark.value;
    deleteDialogOpen.value = false;
    deletingBookmark.value = null;

    router.delete(
        deleteBookmark({
            current_team: currentTeamSlug.value,
            bookmark: bookmark.id,
        }),
        {
            preserveScroll: true,
        },
    );
}

defineExpose({ openDeleteDialog, deleteDialogOpen });
</script>

<template>
    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Bookmark</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-semibold text-foreground">{{
                        deletingBookmark?.title
                    }}</span
                    >? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    variant="outline"
                    class="cursor-pointer"
                    @click="deleteDialogOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    class="cursor-pointer"
                    @click="confirmDelete"
                >
                    <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                    Delete
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
