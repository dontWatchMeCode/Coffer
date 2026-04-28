<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/form/InputError.vue';
import RichTextEditor from '@/components/richtext/RichTextEditor.vue';
import { trimStoredRichText } from '@/components/richtext/storage';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as storeNote } from '@/routes/team/notes';

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const createDialogOpen = ref(false);
const createTitle = ref('');
const createBody = ref('');

function resetCreateForm(): void {
    createTitle.value = '';
    createBody.value = '';
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
            body: trimStoredRichText(createBody.value) || null,
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
    <Dialog :open="createDialogOpen" @update:open="handleCreateClose">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>

        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <DialogHeader>
                    <DialogTitle>Add Note</DialogTitle>
                    <DialogDescription>
                        Capture a note and link it to related team records.
                    </DialogDescription>
                </DialogHeader>

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
                    <Label>Body</Label>
                    <RichTextEditor
                        :model-value="createBody"
                        :editable="true"
                        placeholder="Write the note..."
                        @update:model-value="(value) => (createBody = value)"
                    />
                    <InputError :message="errors.body" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit">Add Note</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
