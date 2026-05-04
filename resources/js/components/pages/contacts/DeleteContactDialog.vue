<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import { DialogDescription } from '@/components/ui/dialog';
import { destroy as deleteContact } from '@/routes/team/contacts';
import type { ContactItem } from '@/types';

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const deleteDialogOpen = ref(false);
const deletingContact = ref<ContactItem | null>(null);

function openDeleteDialog(contact: ContactItem): void {
    deletingContact.value = contact;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!deletingContact.value) {
        return;
    }

    const contact = deletingContact.value;
    deleteDialogOpen.value = false;
    deletingContact.value = null;

    router.delete(
        deleteContact({
            current_team: currentTeamSlug.value,
            contact: contact.id,
        }),
        { preserveScroll: true },
    );
}

defineExpose({ openDeleteDialog, deleteDialogOpen });
</script>

<template>
    <ConfirmDeleteDialog
        v-model:open="deleteDialogOpen"
        title="Delete Contact"
        :confirm-icon="Trash2"
        @confirm="confirmDelete"
    >
        <template #description>
            <DialogDescription>
                Are you sure you want to delete
                <span class="font-semibold text-foreground">{{
                    deletingContact?.name
                }}</span
                >? This action cannot be undone.
            </DialogDescription>
        </template>
    </ConfirmDeleteDialog>
</template>
