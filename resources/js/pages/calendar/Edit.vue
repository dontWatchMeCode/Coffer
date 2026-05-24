<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, Clock, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CalendarEventController from '@/actions/App/Http/Controllers/Calendar/CalendarEventController';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import DetailSection from '@/components/page/DetailSection.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCopyAsMarkdown } from '@/composables/useCopyAsMarkdown';
import { serializeCalendarEvent } from '@/lib/markdown-serializers';
import { taskInputLikeClass } from '@/lib/tasks';
import { index as calendarIndex } from '@/routes/team/calendar';
import type { ActivityHistoryConfig, CalendarEventItem, Team } from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    event: CalendarEventItem;
    recordLinks?: {
        links: LinkRecord[];
        context: LinkContext;
        endpoints: LinkEndpoints;
    } | null;
    recordTags?: {
        tags: RecordTag[];
        context: TagContext;
        endpoints: TagEndpoints;
    } | null;
    activityHistory?: ActivityHistoryConfig;
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const deleteDialogOpen = ref(false);
const isEditing = ref(false);
const isSubmitting = ref(false);
const editFormRef = ref<HTMLFormElement | null>(null);
const editTitle = ref(props.event.title);
const editDescription = ref(props.event.description ?? '');
const editDate = ref(props.event.date ?? '');
const editTime = ref(props.event.time ?? '');

defineOptions({
    inheritAttrs: false,
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

watch(
    () => props.event,
    (event) => {
        if (!isEditing.value) {
            resetEditFields(event);
        }
    },
);

function resetEditFields(event: CalendarEventItem): void {
    editTitle.value = event.title;
    editDescription.value = event.description ?? '';
    editDate.value = event.date ?? '';
    editTime.value = event.time ?? '';
}

function cancelEdit(): void {
    resetEditFields(props.event);
    isEditing.value = false;
}

function formatEventDate(date: string | null | undefined): string {
    if (!date) {
        return '';
    }

    return new Date(`${date}T00:00:00`).toLocaleDateString();
}

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
            time: editTime.value,
        },
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isEditing.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

const { copied, copyError, copyAsMarkdown } = useCopyAsMarkdown();

function handleCopyAsMarkdown(): void {
    copyAsMarkdown(
        serializeCalendarEvent(
            props.event,
            props.recordTags?.tags ?? [],
            props.recordLinks?.links ?? [],
        ),
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
                :on-edit="isEditing ? null : () => (isEditing = true)"
                :on-save="isEditing ? () => editFormRef?.requestSubmit() : null"
                :save-disabled="isSubmitting"
                :on-cancel="isEditing ? cancelEdit : null"
                :cancel-disabled="isSubmitting"
                :on-delete="() => (deleteDialogOpen = true)"
                delete-label="Delete event"
                :delete-disabled="isSubmitting"
                :on-copy-as-markdown="handleCopyAsMarkdown"
                :copy-as-markdown-copied="copied"
                :copy-as-markdown-error="copyError"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #sidebar-top>
                    <ActivityHistoryPanel
                        v-if="activityHistory"
                        :config="activityHistory"
                        :team-slug="currentTeamSlug"
                    />
                </template>

                <template #main>
                    <div v-if="!isEditing" class="space-y-5">
                        <DetailSection
                            title="Date"
                            empty="No date."
                            :has-content="Boolean(event.date)"
                        >
                            <div class="flex items-center gap-2 text-sm">
                                <CalendarDays
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                {{ formatEventDate(event.date) }}
                            </div>
                        </DetailSection>

                        <DetailSection
                            title="Time"
                            empty="No time."
                            :has-content="Boolean(event.time)"
                        >
                            <div class="flex items-center gap-2 text-sm">
                                <Clock class="h-4 w-4 text-muted-foreground" />
                                {{ event.time }}
                            </div>
                        </DetailSection>

                        <DetailSection
                            title="Description"
                            empty="No description."
                            :has-content="Boolean(event.description)"
                        >
                            <p class="text-sm whitespace-pre-wrap">
                                {{ event.description }}
                            </p>
                        </DetailSection>
                    </div>

                    <form
                        v-else
                        ref="editFormRef"
                        class="space-y-5"
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

                        <div class="grid gap-2">
                            <Label for="edit-event-time">Time</Label>
                            <Input
                                id="edit-event-time"
                                v-model="editTime"
                                type="time"
                            />
                            <InputError :message="errors.time" />
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>

        <ConfirmDeleteDialog
            v-model:open="deleteDialogOpen"
            title="Move Event to Trash"
            confirm-label="Move to trash"
            :confirm-icon="Trash2"
            @confirm="confirmDelete"
        >
            <template #description>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-semibold text-foreground">{{
                        event.title
                    }}</span
                    >? You can restore it from calendar trash.
                </DialogDescription>
            </template>
        </ConfirmDeleteDialog>
    </div>
</template>
