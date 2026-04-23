<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CalendarEventController from '@/actions/App/Http/Controllers/Calendar/CalendarEventController';
import InputError from '@/components/InputError.vue';
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

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const deleteDialogOpen = ref(false);

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
        CalendarEventController.destroy({
            current_team: currentTeamSlug.value,
            event: props.event.id,
        }).url,
        {
            onSuccess: () => {
                router.visit(calendarIndex(currentTeamSlug.value).url);
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
        >
            <template #actions>
                <Button
                    variant="destructive"
                    size="sm"
                    class="cursor-pointer"
                    @click="deleteDialogOpen = true"
                >
                    <Trash2 class="mr-1.5 h-4 w-4" />
                    Delete
                </Button>
            </template>
        </PageHeader>

        <div class="flex-1 px-4 py-6">
            <div class="mx-auto max-w-2xl">
                <Form
                    v-bind="
                        CalendarEventController.update.form({
                            current_team: currentTeamSlug,
                            event: event.id,
                        })
                    "
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="router.visit(calendarIndex(currentTeamSlug).url)"
                >
                    <div class="grid gap-2">
                        <Label for="edit-event-title">Title</Label>
                        <Input
                            id="edit-event-title"
                            name="title"
                            :default-value="event.title"
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
                            rows="4"
                            :default-value="event.description ?? ''"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-event-date">Date</Label>
                        <Input
                            id="edit-event-date"
                            name="date"
                            type="date"
                            :default-value="event.date ?? ''"
                            required
                        />
                        <InputError :message="errors.date" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <Button type="submit" :disabled="processing">
                            Save changes
                        </Button>
                    </div>
                </Form>
            </div>
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
