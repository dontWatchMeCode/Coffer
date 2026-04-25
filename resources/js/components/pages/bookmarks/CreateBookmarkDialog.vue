<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
import { taskInputLikeClass } from '@/lib/tasks';
import { store as storeBookmark } from '@/routes/team/bookmarks';

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const createDialogOpen = ref(false);
const createTitle = ref('');
const createUrl = ref('');
const createDescription = ref('');
const createTags = ref('');
const createNotes = ref('');

function resetCreateForm(): void {
    createTitle.value = '';
    createUrl.value = '';
    createDescription.value = '';
    createTags.value = '';
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
            tags: createTags.value
                ? createTags.value
                      .split(',')
                      .map((t) => t.trim())
                      .filter(Boolean)
                : null,
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
    <Dialog :open="createDialogOpen" @update:open="handleCreateClose">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>

        <DialogContent class="max-h-[85vh] overflow-y-auto">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <DialogHeader>
                    <DialogTitle>Add Bookmark</DialogTitle>
                    <DialogDescription>
                        Save a new link for your team.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="create-bookmark-title">Title</Label>
                    <Input
                        id="create-bookmark-title"
                        v-model="createTitle"
                        placeholder="e.g. Laravel Documentation"
                        required
                        autofocus
                    />
                    <p v-if="errors.title" class="text-sm text-red-600">
                        {{ errors.title }}
                    </p>
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
                    <p v-if="errors.url" class="text-sm text-red-600">
                        {{ errors.url }}
                    </p>
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
                    <Label for="create-bookmark-tags">Tags</Label>
                    <Input
                        id="create-bookmark-tags"
                        v-model="createTags"
                        placeholder="docs, reference, tools (comma separated)"
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

                <div class="flex justify-end">
                    <Button type="submit">Add Bookmark</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
