<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import { destroy as destroyCollection } from '@/routes/team/collections';
import type { CollectionItem } from '@/types';

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const deleteDialogOpen = ref(false);
const selectedCollection = ref<CollectionItem | null>(null);

function openDeleteDialog(collection: CollectionItem): void {
    selectedCollection.value = collection;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!selectedCollection.value) {
        return;
    }

    const collection = selectedCollection.value;
    deleteDialogOpen.value = false;
    selectedCollection.value = null;

    router.delete(
        destroyCollection({
            current_team: currentTeamSlug.value,
            collection: collection.id,
        }).url,
        {
            preserveScroll: true,
        },
    );
}

defineExpose({
    openDeleteDialog,
});
</script>

<template>
    <ConfirmDeleteDialog
        v-model:open="deleteDialogOpen"
        title="Delete Collection"
        description="This removes the collection and its links, but not the linked records."
        :confirm-icon="Trash2"
        @confirm="confirmDelete"
    >
        <p v-if="selectedCollection" class="text-sm">
            Delete "{{ selectedCollection.title }}"?
        </p>
    </ConfirmDeleteDialog>
</template>
