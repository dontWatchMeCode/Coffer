<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CalendarEventController from '@/actions/App/Http/Controllers/Calendar/CalendarEventController';
import InputError from '@/components/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import { index as calendarIndex } from '@/routes/team/calendar';
import type { CalendarEventItem, Team } from '@/types';

type Props = {
    event: CalendarEventItem;
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const deleteDialogOpen = ref(false);
const isSubmitting = ref(false);
const formRef = ref<HTMLFormElement | null>(null);
const editTitle = ref(props.event.title);
const editDescription = ref(props.event.description ?? '');
const editDate = ref(props.event.date ?? '');

defineOptions({
    layout: (layoutProps: {
        currentTeam?: Team | null;
        event?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Calendar',
                href: calendarIndex(layoutProps.currentTeam?.slug).url,
            },
            {
                title: layoutProps.event?.title ?? 'Event',
            },
        ],
    }),
});

function confirmDelete(): void {
    if (!props.event) {
        return;
    }

    deleteDialogOpen.value = false;

    router.delete(
        CalendarEventController.destroy.url({
            current_team: currentTeamSlug.value,
            event: props.event.id,
        }),
        {
            onSuccess: () => {
                router.visit(calendarIndex(currentTeamSlug.value).url);
            },
        },
    );
}

function submitEdit(): void {
    isSubmitting.value = true;

    router.patch(
        CalendarEventController.update.url({
            current_team: currentTeamSlug.value,
            event: props.event.id,
        }),
        {
            title: editTitle.value,
            description: editDescription.value,
            date: editDate.value,
        },
        {
            onSuccess: () => {
                router.visit(calendarIndex(currentTeamSlug.value).url);
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="`Edit: ${event.title}`" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="event.title"
            :back-href="calendarIndex(currentTeamSlug).url"
            back-label="Back to calendar"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="event.updatedAt"
                :on-save="() => formRef?.requestSubmit()"
                :on-delete="() => (deleteDialogOpen = true)"
                save-label="Save changes"
                delete-label="Delete event"
                :save-disabled="isSubmitting"
                :delete-disabled="isSubmitting"
            >
                <template #main>
                    <form
                        ref="formRef"
                        class="space-y-4"
                        @submit.prevent="submitEdit"
                    >
                        <div class="grid gap-2">
                            <Label for="edit-event-title">Title</Label>
                            <Input
                                id="edit-event-title"
                                v-model="editTitle"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-event-description"
                                >Description</Label
                            >
                            <textarea
                                id="edit-event-description"
                                v-model="editDescription"
                                :class="taskInputLikeClass"
                                rows="4"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-event-date">Date</Label>
                            <Input
                                id="edit-event-date"
                                v-model="editDate"
                                type="date"
                                required
                            />
                            <InputError :message="errors.date" />
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete event</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete
                        <span class="font-semibold text-foreground">{{
                            event.title
                        }}</span
                        >? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <div class="flex justify-end gap-2 pt-2">
                    <Button
                        variant="outline"
                        class="cursor-pointer"
                        @click="deleteDialogOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        class="cursor-pointer"
                        @click="confirmDelete"
                    >
                        <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                        Delete
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
