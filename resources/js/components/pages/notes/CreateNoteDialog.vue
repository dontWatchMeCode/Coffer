<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import InputError from '@/components/form/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as storeNote } from '@/routes/team/notes';
import type { NoteFormat } from '@/types';

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const createDialogOpen = ref(false);
const createTitle = ref('');
const createFormat = ref<NoteFormat>('text');

function resetCreateForm(): void {
    createTitle.value = '';
    createFormat.value = 'text';
}

function handleCreateClose(value: boolean): void {
    createDialogOpen.value = value;

    if (!value) {
        resetCreateForm();
    }
}

function submitCreate(): void {
    router.post(
        storeNote(currentTeamSlug.value).url,
        {
            title: createTitle.value,
            format: createFormat.value,
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
        title="Add Note"
        description="Capture a note and link it to related team records."
        submit-label="Add Note"
        @update:open="handleCreateClose"
        @submit="submitCreate"
    >
        <template #trigger>
            <slot name="trigger" />
        </template>

        <div class="grid gap-2">
            <Label for="create-note-title">Title</Label>
            <Input
                id="create-note-title"
                v-model="createTitle"
                placeholder="Meeting summary, research, decision..."
                required
                autofocus
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label>Format</Label>
            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    size="sm"
                    :variant="createFormat === 'text' ? 'default' : 'outline'"
                    @click="createFormat = 'text'"
                >
                    Text
                </Button>
                <Button
                    type="button"
                    size="sm"
                    :variant="
                        createFormat === 'excalidraw' ? 'default' : 'outline'
                    "
                    @click="createFormat = 'excalidraw'"
                >
                    Excalidraw
                </Button>
            </div>
            <InputError :message="errors.format" />
        </div>
    </CreateDialog>
</template>
