<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import InputError from '@/components/form/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { titleFromFileName } from '@/lib/files';
import { taskInputLikeClass } from '@/lib/tasks';
import { store as storeFile } from '@/routes/team/files';
import type { FileUploadConstraints } from '@/types';

type Props = {
    uploadConstraints: FileUploadConstraints;
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});
const acceptedMimeTypes = computed(() =>
    props.uploadConstraints.acceptedMimeTypes.join(','),
);
const supportedExtensions = computed(() =>
    props.uploadConstraints.acceptedExtensions
        .map((extension) => extension.toUpperCase())
        .join(', '),
);
const uploadHelpText = computed(
    () =>
        `Supports ${supportedExtensions.value} images up to ${props.uploadConstraints.maxMegabytes} MB.`,
);

const createDialogOpen = ref(false);
const createTitle = ref('');
const createDescription = ref('');
const createFile = ref<File | null>(null);
const inputKey = ref(0);

function resetCreateForm(): void {
    createTitle.value = '';
    createDescription.value = '';
    createFile.value = null;
    inputKey.value += 1;
}

function handleCreateClose(value: boolean): void {
    createDialogOpen.value = value;

    if (!value) {
        resetCreateForm();
    }
}

function submitCreate(): void {
    router.post(
        storeFile(currentTeamSlug.value).url,
        {
            title: createTitle.value,
            description: createDescription.value || null,
            file: createFile.value,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                handleCreateClose(false);
            },
        },
    );
}

function handleFileInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    createFile.value = input.files?.[0] ?? null;

    if (createFile.value && createTitle.value.trim() === '') {
        createTitle.value = titleFromFileName(createFile.value.name);
    }
}

defineExpose({
    createDialogOpen,
    handleCreateClose,
});
</script>

<template>
    <CreateDialog
        :open="createDialogOpen"
        title="Add File"
        description="Upload a private team file. Images are supported first."
        submit-label="Add File"
        @update:open="handleCreateClose"
        @submit="submitCreate"
    >
        <template #trigger>
            <slot name="trigger" />
        </template>

        <div class="grid gap-2">
            <Label for="create-file-title">Title</Label>
            <Input
                id="create-file-title"
                v-model="createTitle"
                placeholder="e.g. Launch moodboard"
                required
                autofocus
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="create-file-upload">Image</Label>
            <Input
                :key="inputKey"
                id="create-file-upload"
                type="file"
                :accept="acceptedMimeTypes"
                required
                @input="handleFileInput"
            />
            <p class="text-sm text-muted-foreground">
                {{ uploadHelpText }}
            </p>
            <InputError :message="errors.file" />
        </div>

        <div class="grid gap-2">
            <Label for="create-file-description">Description</Label>
            <textarea
                id="create-file-description"
                v-model="createDescription"
                :class="taskInputLikeClass"
                rows="3"
                placeholder="Optional notes about this file..."
            />
            <InputError :message="errors.description" />
        </div>
    </CreateDialog>
</template>
