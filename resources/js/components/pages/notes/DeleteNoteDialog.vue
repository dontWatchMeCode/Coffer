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
    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Note</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-semibold text-foreground">{{
                        deletingNote?.title
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
