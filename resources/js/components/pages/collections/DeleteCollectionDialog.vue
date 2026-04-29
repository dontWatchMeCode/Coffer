<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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

    router.delete(
        destroyCollection({
            current_team: currentTeamSlug.value,
            collection: selectedCollection.value.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                selectedCollection.value = null;
            },
        },
    );
}

defineExpose({
    openDeleteDialog,
});
</script>

<template>
    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Collection</DialogTitle>
                <DialogDescription>
                    This removes the collection and its links, but not the
                    linked records.
                </DialogDescription>
            </DialogHeader>

            <p v-if="selectedCollection" class="text-sm">
                Delete "{{ selectedCollection.title }}"?
            </p>

            <DialogFooter>
                <Button variant="outline" @click="deleteDialogOpen = false">
                    Cancel
                </Button>
                <Button variant="destructive" @click="confirmDelete">
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
