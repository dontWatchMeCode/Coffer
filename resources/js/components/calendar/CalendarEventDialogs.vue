<script setup lang="ts">
import { Form, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
import {
    destroy as deleteCalendarEvent,
    store as storeCalendarEvent,
    update as updateCalendarEvent,
} from '@/routes/team/calendar/events';
import type { CalendarEventItem } from '@/types';

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const createDialogOpen = ref(false);
const createFormKey = ref(0);
const createDate = ref('');

function openCreateDialog(dateStr: string): void {
    createDate.value = dateStr;
    createFormKey.value++;
    createDialogOpen.value = true;
}

function handleCreateClose(value: boolean): void {
    createDialogOpen.value = value;

    if (!value) {
        createFormKey.value++;
    }
}

const editEvent = ref<CalendarEventItem | null>(null);
const editFormKey = ref(0);
const editDialogOpen = computed({
    get: () => editEvent.value !== null,
    set: (value: boolean) => {
        if (!value) {
            editEvent.value = null;
            editFormKey.value++;
        }
    },
});

function openEditDialog(event: CalendarEventItem): void {
    editEvent.value = event;
    editFormKey.value++;
}

const deleteDialogOpen = ref(false);
const deletingEvent = ref<CalendarEventItem | null>(null);

function openDeleteDialog(event: CalendarEventItem): void {
    deletingEvent.value = event;
    editEvent.value = null;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!deletingEvent.value) {
        return;
    }

    const event = deletingEvent.value;
    deleteDialogOpen.value = false;
    deletingEvent.value = null;

    router.delete(
        deleteCalendarEvent({
            current_team: currentTeamSlug.value,
            event: event.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                editEvent.value = null;
            },
        },
    );
}

function openCreateDialogNoDate(): void {
    createDate.value = '';
    createFormKey.value++;
    createDialogOpen.value = true;
}

defineExpose({
    openCreateDialog,
    openCreateDialogNoDate,
    openEditDialog,
    openDeleteDialog,
    createDialogOpen,
    handleCreateClose,
});
</script>

<template>
    <Dialog :open="createDialogOpen" @update:open="handleCreateClose">
        <DialogContent>
            <Form
                :key="createFormKey"
                v-bind="storeCalendarEvent.form(currentTeamSlug)"
                reset-on-success
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="handleCreateClose(false)"
            >
                <DialogHeader>
                    <DialogTitle>Create event</DialogTitle>
                    <DialogDescription>
                        Add a new event to the calendar.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="create-event-title">Title</Label>
                    <Input
                        id="create-event-title"
                        name="title"
                        placeholder="Team standup"
                        required
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-event-description">Description</Label>
                    <textarea
                        id="create-event-description"
                        name="description"
                        :class="taskInputLikeClass"
                        rows="3"
                        placeholder="Optional description"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-event-date">Date</Label>
                    <Input
                        id="create-event-date"
                        name="date"
                        type="date"
                        :default-value="createDate"
                        required
                    />
                    <InputError :message="errors.date" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing">
                        Create event
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete event</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-semibold text-foreground">{{
                        deletingEvent?.title
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

    <Dialog v-model:open="editDialogOpen">
        <DialogContent v-if="editEvent">
            <Form
                :key="editFormKey"
                v-bind="
                    updateCalendarEvent.form({
                        current_team: currentTeamSlug,
                        event: editEvent.id,
                    })
                "
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="editEvent = null"
            >
                <DialogHeader>
                    <DialogTitle>Edit event</DialogTitle>
                    <DialogDescription>
                        Update the event details.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-event-title">Title</Label>
                    <Input
                        id="edit-event-title"
                        name="title"
                        :default-value="editEvent.title"
                        required
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-event-description">Description</Label>
                    <textarea
                        id="edit-event-description"
                        name="description"
                        :class="taskInputLikeClass"
                        rows="3"
                        :default-value="editEvent.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-event-date">Date</Label>
                    <Input
                        id="edit-event-date"
                        name="date"
                        type="date"
                        :default-value="editEvent.date ?? ''"
                        required
                    />
                    <InputError :message="errors.date" />
                </div>

                <div class="flex items-center justify-between">
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="cursor-pointer"
                        @click="openDeleteDialog(editEvent)"
                    >
                        <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                        Delete
                    </Button>

                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
