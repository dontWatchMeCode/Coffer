<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ListPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateNoteDialog from '@/components/pages/notes/CreateNoteDialog.vue';
import DeleteNoteDialog from '@/components/pages/notes/DeleteNoteDialog.vue';
import NoteList from '@/components/pages/notes/NoteList.vue';
import { Button } from '@/components/ui/button';
import { useViewMode } from '@/composables/useViewMode';
import { index as notesIndex, show as showNote } from '@/routes/team/notes';
import type { NoteItem, Team } from '@/types';

type Props = {
    notes: NoteItem[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const searchQuery = ref('');

const filteredNotes = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return props.notes;
    }

    return props.notes.filter(
        (note) =>
            note.title.toLowerCase().includes(query) ||
            note.excerpt?.toLowerCase().includes(query) ||
            note.tags.some((tag) => tag.name.toLowerCase().includes(query)),
    );
});

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
                        v-if="filteredNotes.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <NoteList
                    :filtered-notes="filteredNotes"
                    :search-query="searchQuery"
                    :navigate-to-note="navigateToNote"
                    :open-delete-dialog="
                        (note) => deleteDialogRef?.openDeleteDialog(note)
                    "
                    :open-create-dialog="openCreateDialog"
                    :view-mode="viewMode"
                />
            </div>
        </div>
    </div>

    <DeleteNoteDialog ref="deleteDialogRef" />
</template>
