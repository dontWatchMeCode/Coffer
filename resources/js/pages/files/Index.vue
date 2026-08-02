<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateFileDialog from '@/components/pages/files/CreateFileDialog.vue';
import DeleteFileDialog from '@/components/pages/files/DeleteFileDialog.vue';
import FileDetailOverlay from '@/components/pages/files/FileDetailOverlay.vue';
import FileMasonryBoard from '@/components/pages/files/FileMasonryBoard.vue';
import FileTextList from '@/components/pages/files/FileTextList.vue';
import { Button } from '@/components/ui/button';
import { useListDetailOverlay } from '@/composables/useListDetailOverlay';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as filesIndex,
    show as showFile,
    trash as filesTrash,
} from '@/routes/team/files';
import type {
    ActivityHistoryConfig,
    FileItem,
    FileUploadConstraints,
    PaginatedData,
    Team,
} from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    files: PaginatedData<FileItem>;
    uploadConstraints: FileUploadConstraints;
    file?: FileItem;
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

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const filesData = computed(() => props.files?.data ?? []);
const { searchQuery } = useSearch(
    filesIndex(currentTeamSlug.value).url,
    'files',
);

const createDialogRef = ref<InstanceType<typeof CreateFileDialog> | null>(null);
const deleteDialogRef = ref<InstanceType<typeof DeleteFileDialog> | null>(null);
const { viewMode } = useViewMode('files');

const { closeDetail: closeFile, onSavedItem: rememberSavedFile } =
    useListDetailOverlay<FileItem>(
        'files',
        currentTeamSlug.value,
        Boolean(props.files),
        {
            detailItem: () => props.file,
            lists: [{ prop: 'files.data', items: () => props.files?.data }],
        },
    );

function openCreateDialog(): void {
    if (createDialogRef.value) {
        createDialogRef.value.createDialogOpen = true;
    }
}

function navigateToFile(file: FileItem): void {
    router.visit(
        showFile({
            current_team: currentTeamSlug.value,
            file: file.id,
        }).url,
        {
            only: ['file', 'recordLinks', 'recordTags', 'activityHistory'],
            preserveScroll: true,
        },
    );
}

function openDeleteDialog(file: FileItem): void {
    deleteDialogRef.value?.openDeleteDialog(file);
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: {
        currentTeam?: Team | null;
        file?: { title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Files',
                href: filesIndex(pageProps.currentTeam?.slug).url,
            },
            ...(pageProps.file ? [{ title: pageProps.file.title }] : []),
        ],
    }),
});
</script>

<template>
    <Head :title="props.file ? props.file.title : 'Files'" />

    <div v-if="props.files && !props.file">
        <PageHeader
            title="Files"
            description="Private team uploads with image previews and linked records."
        />

        <div class="min-w-0 flex-1 px-4 py-6">
            <div class="mx-auto w-full max-w-7xl">
                <div class="mb-4 flex items-center justify-end gap-3">
                    <SearchInput
                        v-model="searchQuery"
                        data-testid="files-search-input"
                        placeholder="Search files..."
                    />
                </div>

                <div class="min-w-0 space-y-4">
                    <div class="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            title="Trash"
                            as-child
                        >
                            <Link :href="filesTrash(currentTeamSlug).url">
                                <Trash2 class="h-4 w-4" />
                            </Link>
                        </Button>

                        <CreateFileDialog
                            ref="createDialogRef"
                            :upload-constraints="props.uploadConstraints"
                        >
                            <template #trigger>
                                <Button
                                    size="icon"
                                    title="Add file"
                                    class="cursor-pointer"
                                >
                                    <ListPlus class="h-4 w-4" />
                                </Button>
                            </template>
                        </CreateFileDialog>

                        <ViewModeToggle
                            v-if="filesData.length > 0"
                            v-model:view-mode="viewMode"
                        />
                    </div>

                    <InfiniteScroll data="files" :buffer="1200">
                        <FileMasonryBoard
                            v-if="viewMode === 'grid'"
                            :files="filesData"
                            :search-query="searchQuery"
                            :navigate-to-file="navigateToFile"
                            :open-delete-dialog="openDeleteDialog"
                            :open-create-dialog="openCreateDialog"
                        />
                        <FileTextList
                            v-else
                            :files="filesData"
                            :search-query="searchQuery"
                            :navigate-to-file="navigateToFile"
                            :open-delete-dialog="openDeleteDialog"
                            :open-create-dialog="openCreateDialog"
                        />
                    </InfiniteScroll>
                </div>
            </div>
        </div>
    </div>

    <FileDetailOverlay
        v-if="props.file"
        :file="props.file"
        :record-links="props.recordLinks"
        :record-tags="props.recordTags"
        :activity-history="props.activityHistory"
        @close="closeFile(filesIndex(currentTeamSlug).url)"
        @saved="rememberSavedFile"
    />

    <DeleteFileDialog ref="deleteDialogRef" />
</template>
