<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import ExcalidrawEditor from '@/components/excalidraw/ExcalidrawEditor.vue';
import InputError from '@/components/form/InputError.vue';
import RichTextEditor from '@/components/richtext/RichTextEditor.vue';
import { trimStoredRichText } from '@/components/richtext/storage';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as storeNote } from '@/routes/team/notes';
import type { ExcalidrawScene, NoteFormat } from '@/types';

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const createDialogOpen = ref(false);
const createTitle = ref('');
const createBody = ref('');
const createFormat = ref<NoteFormat>('text');
const createDrawingData = ref<ExcalidrawScene | null>(null);

function resetCreateForm(): void {
    createTitle.value = '';
    createBody.value = '';
    createFormat.value = 'text';
    createDrawingData.value = null;
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
            body: trimStoredRichText(createBody.value) || null,
            drawing_data: createDrawingData.value,
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

        <div v-if="createFormat === 'text'" class="grid gap-2">
            <Label>Body</Label>
            <RichTextEditor
                :model-value="createBody"
                :editable="true"
                placeholder="Write the note..."
                @update:model-value="(value) => (createBody = value)"
            />
            <InputError :message="errors.body" />
        </div>

        <div v-else class="grid gap-2">
            <Label>Drawing</Label>
            <ExcalidrawEditor
                v-model="createDrawingData"
                :name="createTitle || 'New drawing note'"
            />
            <InputError :message="errors.drawing_data" />
        </div>
    </CreateDialog>
</template>
