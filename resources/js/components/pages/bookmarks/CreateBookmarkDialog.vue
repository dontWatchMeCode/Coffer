<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import BookmarkFormFields from '@/components/pages/bookmarks/BookmarkFormFields.vue';
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

        <BookmarkFormFields
            v-model:title="createTitle"
            v-model:url="createUrl"
            v-model:description="createDescription"
            v-model:notes="createNotes"
            :errors="errors"
            id-prefix="create-bookmark"
            autofocus
        />
    </CreateDialog>
</template>
