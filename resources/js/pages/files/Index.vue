<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateFileDialog from '@/components/pages/files/CreateFileDialog.vue';
import DeleteFileDialog from '@/components/pages/files/DeleteFileDialog.vue';
import FileMasonryBoard from '@/components/pages/files/FileMasonryBoard.vue';
import FileTextList from '@/components/pages/files/FileTextList.vue';
import { Button } from '@/components/ui/button';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as filesIndex,
    show as showFile,
    trash as filesTrash,
} from '@/routes/team/files';
import type {
    FileItem,
    FileUploadConstraints,
    PaginatedData,
    Team,
} from '@/types';

type Props = {
    files: PaginatedData<FileItem>;
    uploadConstraints: FileUploadConstraints;
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const { searchQuery } = useSearch(
    filesIndex(currentTeamSlug.value).url,
    'files',
);

const createDialogRef = ref<InstanceType<typeof CreateFileDialog> | null>(null);
const deleteDialogRef = ref<InstanceType<typeof DeleteFileDialog> | null>(null);
const { viewMode } = useViewMode('files');

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
    );
}

function openDeleteDialog(file: FileItem): void {
    deleteDialogRef.value?.openDeleteDialog(file);
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Files',
                href: filesIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Files" />

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
                        v-if="props.files.data.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <InfiniteScroll data="files">
                    <FileMasonryBoard
                        v-if="viewMode === 'grid'"
                        :files="props.files.data"
                        :search-query="searchQuery"
                        :navigate-to-file="navigateToFile"
                        :open-delete-dialog="openDeleteDialog"
                        :open-create-dialog="openCreateDialog"
                    />
                    <FileTextList
                        v-else
                        :files="props.files.data"
                        :search-query="searchQuery"
                        :navigate-to-file="navigateToFile"
                        :open-delete-dialog="openDeleteDialog"
                        :open-create-dialog="openCreateDialog"
                    />
                </InfiniteScroll>
            </div>
        </div>
    </div>

    <DeleteFileDialog ref="deleteDialogRef" />
</template>
