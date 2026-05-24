<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import BlockEditor from '@/components/blocks/BlockEditor.vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteNoteDialog from '@/components/pages/notes/DeleteNoteDialog.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCopyAsMarkdown } from '@/composables/useCopyAsMarkdown';
import { serializeNote } from '@/lib/markdown-serializers';
import { index as notesIndex, update as updateNote } from '@/routes/team/notes';
import type { ActivityHistoryConfig, NoteItem, RteBlock, Team } from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    note: NoteItem;
    startInEditMode?: boolean;
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

const isEditing = ref(props.startInEditMode === true);
const editTitle = ref(props.note.title);
const editBlocks = ref<RteBlock[]>(
    JSON.parse(JSON.stringify(props.note.blocks ?? [])),
);
const isSubmitting = ref(false);
const deleteDialogRef = ref<InstanceType<typeof DeleteNoteDialog> | null>(null);
const editFormRef = ref<HTMLFormElement | null>(null);

const { copied, copyError, copyAsMarkdown } = useCopyAsMarkdown();

function handleCopyAsMarkdown(): void {
    copyAsMarkdown(
        serializeNote(
            props.note,
            props.recordTags?.tags ?? [],
            props.recordLinks?.links ?? [],
        ),
    );
}

watch(
    () => props.note,
    (note) => {
        if (!isEditing.value) {
            editTitle.value = note.title;
            editBlocks.value = JSON.parse(JSON.stringify(note.blocks ?? []));
        }
    },
);

function cancelEdit(): void {
    editTitle.value = props.note.title;
    editBlocks.value = JSON.parse(JSON.stringify(props.note.blocks ?? []));
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
            blocks: editBlocks.value.map((b) => ({
                id: b.id > 0 ? b.id : undefined,
                type: b.type,
                position: b.position,
                payload: b.payload,
            })),
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
    inheritAttrs: false,
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
                :on-edit="isEditing ? null : () => (isEditing = true)"
                :on-save="isEditing ? () => editFormRef?.requestSubmit() : null"
                :save-disabled="isSubmitting"
                :on-cancel="isEditing ? cancelEdit : null"
                :cancel-disabled="isSubmitting"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(note)"
                delete-label="Delete note"
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
                    <div v-if="!isEditing">
                        <BlockEditor :blocks="note.blocks" :editable="false" />
                    </div>

                    <form
                        v-else
                        ref="editFormRef"
                        class="space-y-4"
                        @submit.prevent="submitEdit"
                    >
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
                                <BlockEditor
                                    v-model:blocks="editBlocks"
                                    :editable="true"
                                    :name="editTitle || note.title"
                                />
                                <InputError :message="errors.blocks" />
                            </div>
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteNoteDialog ref="deleteDialogRef" />
</template>
