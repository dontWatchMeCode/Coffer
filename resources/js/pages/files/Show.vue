<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, usePage, router } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import DetailLinkRow from '@/components/page/DetailLinkRow.vue';
import DetailSection from '@/components/page/DetailSection.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteFileDialog from '@/components/pages/files/DeleteFileDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import { formatBytes, formatDateTime } from '@/lib/utils';
import { index as filesIndex, update as updateFile } from '@/routes/team/files';
import type { ActivityHistoryConfig, FileItem, Team } from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    file: FileItem;
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
const isEditing = ref(false);
const editTitle = ref(props.file.title);
const editDescription = ref(props.file.description ?? '');
const isSubmitting = ref(false);
const editFormRef = ref<HTMLFormElement | null>(null);
const deleteDialogRef = ref<InstanceType<typeof DeleteFileDialog> | null>(null);

watch(
    () => props.file,
    (file) => {
        if (!isEditing.value) {
            editTitle.value = file.title;
            editDescription.value = file.description ?? '';
        }
    },
);

function cancelEdit(): void {
    editTitle.value = props.file.title;
    editDescription.value = props.file.description ?? '';
    isEditing.value = false;
}

function submitEdit(): void {
    isSubmitting.value = true;

    router.patch(
        updateFile({
            current_team: currentTeamSlug.value,
            file: props.file.id,
        }).url,
        {
            title: editTitle.value,
            description: editDescription.value || null,
        },
        {
            preserveScroll: true,
            preserveState: true,
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
        file?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Files',
                href: filesIndex(layoutProps.currentTeam?.slug).url,
            },
            {
                title: layoutProps.file?.title ?? 'File',
            },
        ],
    }),
});
</script>

<template>
    <Head :title="file.title" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="file.title"
            description="Preview file details and related records."
            :back-href="filesIndex(currentTeamSlug).url"
            back-label="Back to files"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="file.updatedAt"
                :on-edit="isEditing ? null : () => (isEditing = true)"
                :on-save="isEditing ? () => editFormRef?.requestSubmit() : null"
                :save-disabled="isSubmitting"
                :on-cancel="isEditing ? cancelEdit : null"
                :cancel-disabled="isSubmitting"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(file)"
                delete-label="Delete file"
                :delete-disabled="isSubmitting"
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
                    <div v-if="!isEditing" class="space-y-6">
                        <div
                            v-if="file.isImage"
                            class="overflow-hidden rounded-xl border bg-muted"
                        >
                            <img
                                :src="file.previewUrl"
                                :alt="file.title"
                                class="mx-auto max-h-[70vh] w-auto object-contain"
                            />
                        </div>

                        <div
                            v-else
                            class="rounded-xl border bg-card p-8 text-center"
                        >
                            <p class="text-sm text-muted-foreground">
                                Preview is not available for this file type yet.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <Button
                                as="a"
                                :href="file.downloadUrl"
                                class="gap-2"
                            >
                                <Download class="h-4 w-4" />
                                Download
                            </Button>
                        </div>

                        <DetailSection
                            title="Description"
                            empty="No description."
                            :has-content="Boolean(file.description)"
                        >
                            <p class="text-sm whitespace-pre-wrap">
                                {{ file.description }}
                            </p>
                        </DetailSection>

                        <DetailSection title="File Details">
                            <div class="space-y-3 text-sm">
                                <DetailLinkRow
                                    :href="file.downloadUrl"
                                    :value="file.originalName"
                                />
                                <div class="grid grid-cols-[8rem_1fr] gap-3">
                                    <span class="text-muted-foreground"
                                        >Type</span
                                    >
                                    <span>{{ file.mimeType }}</span>
                                    <span class="text-muted-foreground"
                                        >Size</span
                                    >
                                    <span>{{ formatBytes(file.size) }}</span>
                                    <span
                                        v-if="file.width"
                                        class="text-muted-foreground"
                                        >Dimensions</span
                                    >
                                    <span v-if="file.width"
                                        >{{ file.width }} ×
                                        {{ file.height }}</span
                                    >
                                    <span class="text-muted-foreground"
                                        >Updated</span
                                    >
                                    <span>{{
                                        formatDateTime(file.updatedAt)
                                    }}</span>
                                </div>
                            </div>
                        </DetailSection>
                    </div>

                    <form
                        v-else
                        ref="editFormRef"
                        class="space-y-5"
                        @submit.prevent="submitEdit"
                    >
                        <div class="grid gap-2">
                            <Label for="edit-file-title">Title</Label>
                            <Input
                                id="edit-file-title"
                                v-model="editTitle"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-file-description"
                                >Description</Label
                            >
                            <textarea
                                id="edit-file-description"
                                v-model="editDescription"
                                :class="taskInputLikeClass"
                                rows="6"
                                placeholder="Optional description..."
                            />
                            <InputError :message="errors.description" />
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteFileDialog ref="deleteDialogRef" />
</template>
