<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import InputError from '@/components/form/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import { store as storeBookmark } from '@/routes/team/bookmarks';

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const createDialogOpen = ref(false);
const createTitle = ref('');
const createUrl = ref('');
const createDescription = ref('');
const createNotes = ref('');

function resetCreateForm(): void {
    createTitle.value = '';
    createUrl.value = '';
    createDescription.value = '';
    createNotes.value = '';
}

function handleCreateClose(value: boolean): void {
    createDialogOpen.value = value;

    if (!value) {
        resetCreateForm();
    }
}

function submitCreate(): void {
    router.post(
        storeBookmark(currentTeamSlug.value).url,
        {
            title: createTitle.value,
            url: createUrl.value,
            description: createDescription.value || null,
            notes: createNotes.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                handleCreateClose(false);
            },
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
        title="Add Bookmark"
        description="Save a new link for your team."
        submit-label="Add Bookmark"
        @update:open="handleCreateClose"
        @submit="submitCreate"
    >
        <template #trigger>
            <slot name="trigger" />
        </template>

        <div class="grid gap-2">
            <Label for="create-bookmark-title">Title</Label>
            <Input
                id="create-bookmark-title"
                v-model="createTitle"
                placeholder="e.g. Laravel Documentation"
                required
                autofocus
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="create-bookmark-url">URL</Label>
            <Input
                id="create-bookmark-url"
                v-model="createUrl"
                type="url"
                placeholder="https://example.com"
                required
            />
            <InputError :message="errors.url" />
        </div>

        <div class="grid gap-2">
            <Label for="create-bookmark-description">Description</Label>
            <Input
                id="create-bookmark-description"
                v-model="createDescription"
                placeholder="Short description of the link"
            />
        </div>

        <div class="grid gap-2">
            <Label for="create-bookmark-notes">Notes</Label>
            <textarea
                id="create-bookmark-notes"
                v-model="createNotes"
                :class="taskInputLikeClass"
                rows="3"
                placeholder="Any additional notes about this link..."
            />
        </div>
    </CreateDialog>
</template>
