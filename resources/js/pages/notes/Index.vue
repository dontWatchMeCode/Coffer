<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateNoteDialog from '@/components/pages/notes/CreateNoteDialog.vue';
import DeleteNoteDialog from '@/components/pages/notes/DeleteNoteDialog.vue';
import NoteList from '@/components/pages/notes/NoteList.vue';
import { Button } from '@/components/ui/button';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as notesIndex,
    show as showNote,
    trash as notesTrash,
} from '@/routes/team/notes';
import type { NoteItem, PaginatedData, Team } from '@/types';

type Props = {
    notes: PaginatedData<NoteItem>;
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const { searchQuery } = useSearch(
    notesIndex(currentTeamSlug.value).url,
    'notes',
);

const createDialogRef = ref<InstanceType<typeof CreateNoteDialog> | null>(null);
const deleteDialogRef = ref<InstanceType<typeof DeleteNoteDialog> | null>(null);

function openCreateDialog(): void {
    if (createDialogRef.value) {
        createDialogRef.value.createDialogOpen = true;
    }
}

function navigateToNote(note: NoteItem): void {
    router.visit(
        showNote({
            current_team: currentTeamSlug.value,
            note: note.id,
        }).url,
    );
}

const { viewMode } = useViewMode('notes');

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Notes',
                href: notesIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Notes" />

    <PageHeader
        title="Notes"
        description="Capture team knowledge and connect it to related records."
    />

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <SearchInput
                    v-model="searchQuery"
                    data-testid="notes-search-input"
                    placeholder="Search notes..."
                />
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-end gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        title="Trash"
                        as-child
                    >
                        <Link :href="notesTrash(currentTeamSlug).url">
                            <Trash2 class="h-4 w-4" />
                        </Link>
                    </Button>

                    <CreateNoteDialog ref="createDialogRef">
                        <template #trigger>
                            <Button
                                size="icon"
                                title="Create note"
                                class="cursor-pointer"
                            >
                                <ListPlus class="h-4 w-4" />
                            </Button>
                        </template>
                    </CreateNoteDialog>

                    <ViewModeToggle
                        v-if="props.notes.data.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <InfiniteScroll data="notes">
                    <NoteList
                        :filtered-notes="props.notes.data"
                        :search-query="searchQuery"
                        :navigate-to-note="navigateToNote"
                        :open-delete-dialog="
                            (note) => deleteDialogRef?.openDeleteDialog(note)
                        "
                        :open-create-dialog="openCreateDialog"
                        :view-mode="viewMode"
                    />
                </InfiniteScroll>
            </div>
        </div>
    </div>

    <DeleteNoteDialog ref="deleteDialogRef" />
</template>
