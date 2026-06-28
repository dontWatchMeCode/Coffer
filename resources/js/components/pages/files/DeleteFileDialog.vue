<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import { DialogDescription } from '@/components/ui/dialog';
import { destroy as deleteFile } from '@/routes/team/files';
import type { FileItem } from '@/types';

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const deleteDialogOpen = ref(false);
const deletingFile = ref<FileItem | null>(null);

function openDeleteDialog(file: FileItem): void {
    deletingFile.value = file;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!deletingFile.value) {
        return;
    }

    const file = deletingFile.value;
    deleteDialogOpen.value = false;
    deletingFile.value = null;

    router.delete(
        deleteFile({
            current_team: currentTeamSlug.value,
            file: file.id,
        }).url,
        { preserveScroll: true },
    );
}

defineExpose({ openDeleteDialog, deleteDialogOpen });
</script>

<template>
    <ConfirmDeleteDialog
        v-model:open="deleteDialogOpen"
        title="Move File to Trash"
        confirm-label="Move to trash"
        :confirm-icon="Trash2"
        @confirm="confirmDelete"
    >
        <template #description>
            <DialogDescription>
                Are you sure you want to delete
                <span class="font-semibold text-foreground">{{
                    deletingFile?.title
                }}</span
                >? You can restore it from file trash.
            </DialogDescription>
        </template>
    </ConfirmDeleteDialog>
</template>
