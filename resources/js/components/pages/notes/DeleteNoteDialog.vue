<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import { DialogDescription } from '@/components/ui/dialog';
import { destroy as deleteNote } from '@/routes/team/notes';
import type { NoteItem } from '@/types';

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const deleteDialogOpen = ref(false);
const deletingNote = ref<NoteItem | null>(null);

function openDeleteDialog(note: NoteItem): void {
    deletingNote.value = note;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!deletingNote.value) {
        return;
    }

    const note = deletingNote.value;
    deleteDialogOpen.value = false;
    deletingNote.value = null;

    router.delete(
        deleteNote({
            current_team: currentTeamSlug.value,
            note: note.id,
        }),
        { preserveScroll: true },
    );
}

defineExpose({ openDeleteDialog, deleteDialogOpen });
</script>

<template>
    <ConfirmDeleteDialog
        v-model:open="deleteDialogOpen"
        title="Delete Note"
        :confirm-icon="Trash2"
        @confirm="confirmDelete"
    >
        <template #description>
            <DialogDescription>
                Are you sure you want to delete
                <span class="font-semibold text-foreground">{{
                    deletingNote?.title
                }}</span
                >? This action cannot be undone.
            </DialogDescription>
        </template>
    </ConfirmDeleteDialog>
</template>
