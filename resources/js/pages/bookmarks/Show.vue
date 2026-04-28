<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteBookmarkDialog from '@/components/pages/bookmarks/DeleteBookmarkDialog.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import {
    index as bookmarksIndex,
    update as updateBookmark,
} from '@/routes/team/bookmarks';
import type { BookmarkItem, Team } from '@/types';
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
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

defineOptions({
    layout: (layoutProps: {
        currentTeam?: Team | null;
        bookmark?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Bookmarks',
                href: bookmarksIndex(layoutProps.currentTeam?.slug).url,
            },
            {
                title: layoutProps.bookmark?.title ?? 'Bookmark',
            },
        ],
    }),
});

const editTitle = ref(props.bookmark.title);
const editUrl = ref(props.bookmark.url);
const editDescription = ref(props.bookmark.description ?? '');
const editNotes = ref(props.bookmark.notes ?? '');
const editIsArchived = ref(props.bookmark.isArchived);
const isSubmitting = ref(false);
const formRef = ref<HTMLFormElement | null>(null);

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
            is_archived: editIsArchived.value,
        },
        {
            onSuccess: () => {
                router.visit(bookmarksIndex(currentTeamSlug.value).url);
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

const deleteDialogRef = ref<InstanceType<typeof DeleteBookmarkDialog> | null>(
    null,
);
</script>

<template>
    <Head :title="bookmark.title" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="bookmark.title"
            description="Edit bookmark details."
            :back-href="bookmarksIndex(currentTeamSlug).url"
            back-label="Back to bookmarks"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="bookmark.updatedAt"
                :on-save="() => formRef?.requestSubmit()"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(bookmark)"
                save-label="Save changes"
                delete-label="Delete bookmark"
                :save-disabled="isSubmitting"
                :delete-disabled="isSubmitting"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #main>
                    <form
                        ref="formRef"
                        class="space-y-4"
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

                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="edit-bookmark-archived"
                                v-model="editIsArchived"
                            />
                            <Label
                                for="edit-bookmark-archived"
                                class="cursor-pointer text-sm font-normal"
                            >
                                Archived
                            </Label>
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteBookmarkDialog ref="deleteDialogRef" />
</template>
