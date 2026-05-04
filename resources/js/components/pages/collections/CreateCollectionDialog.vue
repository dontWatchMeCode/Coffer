<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import InputError from '@/components/form/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import { store as storeCollection } from '@/routes/team/collections';

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const createDialogOpen = ref(false);
const createTitle = ref('');
const createDescription = ref('');

function resetCreateForm(): void {
    createTitle.value = '';
    createDescription.value = '';
}

function handleCreateClose(value: boolean): void {
    createDialogOpen.value = value;

    if (!value) {
        resetCreateForm();
    }
}

function submitCreate(): void {
    router.post(
        storeCollection(currentTeamSlug.value).url,
        {
            title: createTitle.value,
            description: createDescription.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => handleCreateClose(false),
        },
    );
}

defineExpose({
    createDialogOpen,
    handleCreateClose,
});
</script>

<template>
    <CreateDialog
        :open="createDialogOpen"
        title="Add Collection"
        description="Group related records into a focused collection."
        submit-label="Add Collection"
        @update:open="handleCreateClose"
        @submit="submitCreate"
    >
        <template #trigger>
            <slot name="trigger" />
        </template>

        <div class="grid gap-2">
            <Label for="create-collection-title">Title</Label>
            <Input
                id="create-collection-title"
                v-model="createTitle"
                placeholder="Launch plan, hiring packet, research set..."
                required
                autofocus
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="create-collection-description">Description</Label>
            <textarea
                id="create-collection-description"
                v-model="createDescription"
                :class="taskInputLikeClass"
                rows="4"
                placeholder="What belongs in this collection?"
            />
            <InputError :message="errors.description" />
        </div>
    </CreateDialog>
</template>
