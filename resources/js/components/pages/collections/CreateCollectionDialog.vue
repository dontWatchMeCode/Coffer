<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/form/InputError.vue';
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
    <Dialog :open="createDialogOpen" @update:open="handleCreateClose">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>

        <DialogContent class="max-h-[85vh] overflow-y-auto">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <DialogHeader>
                    <DialogTitle>Add Collection</DialogTitle>
                    <DialogDescription>
                        Group related records into a focused collection.
                    </DialogDescription>
                </DialogHeader>

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
                    <Label for="create-collection-description"
                        >Description</Label
                    >
                    <textarea
                        id="create-collection-description"
                        v-model="createDescription"
                        :class="taskInputLikeClass"
                        rows="4"
                        placeholder="What belongs in this collection?"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit">Add Collection</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
