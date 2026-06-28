<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ExternalLink } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import DetailLinkRow from '@/components/page/DetailLinkRow.vue';
import DetailSection from '@/components/page/DetailSection.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteBookmarkDialog from '@/components/pages/bookmarks/DeleteBookmarkDialog.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCopyAsMarkdown } from '@/composables/useCopyAsMarkdown';
import { serializeBookmark } from '@/lib/markdown-serializers';
import { taskInputLikeClass } from '@/lib/tasks';
import { update as updateBookmark } from '@/routes/team/bookmarks';
import type { ActivityHistoryConfig, BookmarkItem } from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    bookmark: BookmarkItem;
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

type BookmarkPageProps = PageProps & {
    bookmark?: BookmarkItem;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    saved: [bookmark: BookmarkItem];
}>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const isEditing = ref(false);

const editTitle = ref(props.bookmark.title);
const editUrl = ref(props.bookmark.url);
const editDescription = ref(props.bookmark.description ?? '');
const editNotes = ref(props.bookmark.notes ?? '');
const isSubmitting = ref(false);
const editFormRef = ref<HTMLFormElement | null>(null);
const deleteDialogRef = ref<InstanceType<typeof DeleteBookmarkDialog> | null>(
    null,
);

watch(
    () => props.bookmark,
    (bookmark) => {
        if (!isEditing.value) {
            resetEditFields(bookmark);
        }
    },
);

function resetEditFields(bookmark: BookmarkItem): void {
    editTitle.value = bookmark.title;
    editUrl.value = bookmark.url;
    editDescription.value = bookmark.description ?? '';
    editNotes.value = bookmark.notes ?? '';
}

function close(): void {
    emit('close');
}

function cancelEdit(): void {
    resetEditFields(props.bookmark);
    isEditing.value = false;
}

function submitEdit(): void {
    isSubmitting.value = true;

    router.patch(
        updateBookmark({
            current_team: currentTeamSlug.value,
            bookmark: props.bookmark.id,
        }).url,
        {
            title: editTitle.value,
            url: editUrl.value,
            description: editDescription.value || null,
            notes: editNotes.value || null,
        },
        {
            only: ['bookmark', 'recordLinks', 'recordTags', 'activityHistory'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: (response) => {
                const savedBookmark = (response.props as BookmarkPageProps)
                    .bookmark ?? {
                    ...props.bookmark,
                    title: editTitle.value,
                    url: editUrl.value,
                    description: editDescription.value || null,
                    notes: editNotes.value || null,
                };

                emit('saved', savedBookmark);
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
        serializeBookmark(
            props.bookmark,
            props.recordTags?.tags ?? [],
            props.recordLinks?.links ?? [],
        ),
    );
}
</script>

<template>
    <Head :title="bookmark.title" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="bookmark.title"
            description="Review bookmark details and related records."
            back-label="Back to bookmarks"
            :back-handler="close"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="bookmark.updatedAt"
                :on-edit="isEditing ? null : () => (isEditing = true)"
                :on-save="isEditing ? () => editFormRef?.requestSubmit() : null"
                :save-disabled="isSubmitting"
                :on-cancel="isEditing ? cancelEdit : null"
                :cancel-disabled="isSubmitting"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(bookmark)"
                delete-label="Delete bookmark"
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
                        <DetailSection title="URL">
                            <DetailLinkRow
                                :href="bookmark.url"
                                :value="bookmark.url"
                                external
                            >
                                <template #icon>
                                    <ExternalLink
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                </template>
                            </DetailLinkRow>
                        </DetailSection>

                        <DetailSection
                            title="Description"
                            empty="No description."
                            :has-content="Boolean(bookmark.description)"
                        >
                            <p class="text-sm whitespace-pre-wrap">
                                {{ bookmark.description }}
                            </p>
                        </DetailSection>

                        <DetailSection
                            title="Notes"
                            empty="No notes."
                            :has-content="Boolean(bookmark.notes)"
                        >
                            <p class="text-sm whitespace-pre-wrap">
                                {{ bookmark.notes }}
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
                            <Label for="edit-bookmark-title">Title</Label>
                            <Input
                                id="edit-bookmark-title"
                                v-model="editTitle"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-bookmark-url">URL</Label>
                            <Input
                                id="edit-bookmark-url"
                                v-model="editUrl"
                                type="url"
                                required
                            />
                            <InputError :message="errors.url" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-bookmark-description"
                                >Description</Label
                            >
                            <Input
                                id="edit-bookmark-description"
                                v-model="editDescription"
                                placeholder="Short description"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-bookmark-notes">Notes</Label>
                            <textarea
                                id="edit-bookmark-notes"
                                v-model="editNotes"
                                :class="taskInputLikeClass"
                                rows="4"
                                placeholder="Additional notes about this link..."
                            />
                            <InputError :message="errors.notes" />
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>

        <DeleteBookmarkDialog ref="deleteDialogRef" />
    </div>
</template>
