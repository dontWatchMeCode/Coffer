<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CalendarEventController from '@/actions/App/Http/Controllers/Calendar/CalendarEventController';
import InputError from '@/components/form/InputError.vue';
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

function openCreateDialogNoDate(): void {
    createDate.value = '';
    createFormKey.value++;
    createDialogOpen.value = true;
}

defineExpose({
    openCreateDialog,
    openCreateDialogNoDate,
    createDialogOpen,
    handleCreateClose,
});
</script>

<template>
    <Dialog :open="createDialogOpen" @update:open="handleCreateClose">
        <DialogContent>
            <Form
                :key="createFormKey"
                v-bind="CalendarEventController.store.form(currentTeamSlug)"
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
</template>
