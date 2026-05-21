<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import { DialogDescription } from '@/components/ui/dialog';
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
        { preserveScroll: true },
    );
}

defineExpose({ openDeleteDialog, deleteDialogOpen });
</script>

<template>
    <ConfirmDeleteDialog
        v-model:open="deleteDialogOpen"
        title="Move Bookmark to Trash"
        confirm-label="Move to trash"
        :confirm-icon="Trash2"
        @confirm="confirmDelete"
    >
        <template #description>
            <DialogDescription>
                Are you sure you want to delete
                <span class="font-semibold text-foreground">{{
                    deletingBookmark?.title
                }}</span
                >? You can restore it from bookmark trash.
            </DialogDescription>
        </template>
    </ConfirmDeleteDialog>
</template>
