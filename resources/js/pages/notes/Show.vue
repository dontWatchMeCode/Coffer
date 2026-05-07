<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import ExcalidrawEditor from '@/components/excalidraw/ExcalidrawEditor.vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteNoteDialog from '@/components/pages/notes/DeleteNoteDialog.vue';
import RichTextEditor from '@/components/richtext/RichTextEditor.vue';
import { trimStoredRichText } from '@/components/richtext/storage';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as notesIndex, update as updateNote } from '@/routes/team/notes';
import type {
    ActivityHistoryItem,
    ExcalidrawScene,
    NoteFormat,
    NoteItem,
    Team,
} from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    note: NoteItem;
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
    activityHistory?: ActivityHistoryItem[];
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const isEditing = ref(false);
const editTitle = ref(props.note.title);
const editBody = ref(props.note.body ?? '');
const editFormat = ref<NoteFormat>(props.note.format);
const editDrawingData = ref<ExcalidrawScene | null>(
    props.note.drawingData ?? null,
);
const isSubmitting = ref(false);
const deleteDialogRef = ref<InstanceType<typeof DeleteNoteDialog> | null>(null);

watch(
    () => props.note,
    (note) => {
        if (!isEditing.value) {
            editTitle.value = note.title;
            editBody.value = note.body ?? '';
            editFormat.value = note.format;
            editDrawingData.value = note.drawingData ?? null;
        }
    },
);

function cancelEdit(): void {
    editTitle.value = props.note.title;
    editBody.value = props.note.body ?? '';
    editFormat.value = props.note.format;
    editDrawingData.value = props.note.drawingData ?? null;
    isEditing.value = false;
}

function submitEdit(): void {
    isSubmitting.value = true;

    router.patch(
        updateNote({
            current_team: currentTeamSlug.value,
            note: props.note.id,
        }).url,
        {
            title: editTitle.value,
            format: editFormat.value,
            body: trimStoredRichText(editBody.value) || null,
            drawing_data: editDrawingData.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

defineOptions({
    layout: (layoutProps: {
        currentTeam?: Team | null;
        note?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Notes',
                href: notesIndex(layoutProps.currentTeam?.slug).url,
            },
            {
                title: layoutProps.note?.title ?? 'Note',
            },
        ],
    }),
});
</script>

<template>
    <Head :title="note.title" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="note.title"
            description="Edit note details and related records."
            :back-href="notesIndex(currentTeamSlug).url"
            back-label="Back to notes"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="note.updatedAt"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(note)"
                delete-label="Delete note"
                :delete-disabled="isSubmitting"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #sidebar-top>
                    <ActivityHistoryPanel
                        v-if="activityHistory"
                        :activities="activityHistory"
                    />
                </template>

                <template #main>
                    <div v-if="!isEditing" class="space-y-4">
                        <div
                            :class="
                                note.format === 'excalidraw'
                                    ? 'excalidraw-preview'
                                    : ''
                            "
                        >
                            <ExcalidrawEditor
                                v-if="note.format === 'excalidraw'"
                                :key="`view-${note.id}-${note.updatedAt}`"
                                :model-value="note.drawingData"
                                :readonly="true"
                                :name="note.title"
                            />
                            <RichTextEditor
                                v-else-if="note.body"
                                :model-value="note.body"
                                :editable="false"
                                :on-activate="() => (isEditing = true)"
                            />
                            <div v-else class="text-muted-foreground italic">
                                No body provided.
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="isEditing = true"
                            >
                                Edit
                            </Button>
                        </div>
                    </div>

                    <form v-else class="space-y-4" @submit.prevent="submitEdit">
                        <div class="rounded-lg border bg-card p-4">
                            <div class="space-y-4">
                                <div class="grid gap-2">
                                    <Label for="edit-note-title">Title</Label>
                                    <Input
                                        id="edit-note-title"
                                        v-model="editTitle"
                                        required
                                    />
                                    <InputError :message="errors.title" />
                                </div>

                                <div class="grid gap-2">
                                    <Label>Format</Label>
                                    <div class="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            :variant="
                                                editFormat === 'text'
                                                    ? 'default'
                                                    : 'outline'
                                            "
                                            @click="editFormat = 'text'"
                                        >
                                            Text
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            :variant="
                                                editFormat === 'excalidraw'
                                                    ? 'default'
                                                    : 'outline'
                                            "
                                            @click="editFormat = 'excalidraw'"
                                        >
                                            Excalidraw
                                        </Button>
                                    </div>
                                    <InputError :message="errors.format" />
                                </div>

                                <div class="grid gap-2">
                                    <Label>{{
                                        editFormat === 'text'
                                            ? 'Body'
                                            : 'Drawing'
                                    }}</Label>
                                    <RichTextEditor
                                        v-if="editFormat === 'text'"
                                        :model-value="editBody"
                                        :editable="true"
                                        placeholder="Write the note..."
                                        @update:model-value="
                                            (value) => (editBody = value)
                                        "
                                    />
                                    <ExcalidrawEditor
                                        v-else
                                        v-model="editDrawingData"
                                        :name="editTitle || note.title"
                                    />
                                    <InputError :message="errors.body" />
                                    <InputError
                                        :message="errors.drawing_data"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                type="button"
                                @click="cancelEdit"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="isSubmitting">
                                Save changes
                            </Button>
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteNoteDialog ref="deleteDialogRef" />
</template>
