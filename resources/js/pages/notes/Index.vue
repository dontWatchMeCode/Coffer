<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateNoteDialog from '@/components/pages/notes/CreateNoteDialog.vue';
import DeleteNoteDialog from '@/components/pages/notes/DeleteNoteDialog.vue';
import NoteDetailOverlay from '@/components/pages/notes/NoteDetailOverlay.vue';
import NoteList from '@/components/pages/notes/NoteList.vue';
import { Button } from '@/components/ui/button';
import { useListDetailOverlay } from '@/composables/useListDetailOverlay';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as notesIndex,
    show as showNote,
    trash as notesTrash,
} from '@/routes/team/notes';
import type {
    ActivityHistoryConfig,
    NoteItem,
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
    notes: PaginatedData<NoteItem>;
    note?: NoteItem;
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

type NotePageProps = PageProps & Partial<Props>;

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const { searchQuery } = useSearch(
    notesIndex(currentTeamSlug.value).url,
    'notes',
);

const {
    closeDetail,
    rememberSavedItem,
    getPendingSavedItem,
    clearPendingSavedItem,
} = useListDetailOverlay('notes', currentTeamSlug.value, Boolean(props.notes));

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
        {
            only: ['note', 'recordLinks', 'recordTags', 'activityHistory'],
            preserveScroll: true,
        },
    );
}

const { viewMode } = useViewMode('notes');

function replaceLoadedNote(note: NoteItem): boolean {
    if (!props.notes?.data.some((n) => n.id === note.id)) {
        return false;
    }

    router.replaceProp<NotePageProps>('notes.data', (notes: unknown) => {
        if (!Array.isArray(notes)) {
            return notes;
        }

        return notes.map((n) => (n.id === note.id ? note : n));
    });

    return true;
}

function applyPendingSavedNote(): void {
    if (props.note || !props.notes) {
        return;
    }

    const note = getPendingSavedItem<NoteItem & { id: number }>();

    if (!note || typeof note.id !== 'number') {
        clearPendingSavedItem();

        return;
    }

    replaceLoadedNote(note);
    clearPendingSavedItem();
}

function closeNote(): void {
    closeDetail(notesIndex(currentTeamSlug.value).url);
}

function onSaved(note: NoteItem): void {
    rememberSavedItem(note);
    replaceLoadedNote(note);
}

watch(
    () => [props.note?.id, props.notes?.data],
    () => applyPendingSavedNote(),
    { immediate: true, flush: 'post' },
);

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: {
        currentTeam?: Team | null;
        note?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Notes',
                href: notesIndex(pageProps.currentTeam?.slug).url,
            },
            ...(pageProps.note ? [{ title: pageProps.note.title }] : []),
        ],
    }),
});
</script>

<template>
    <Head :title="props.note ? props.note.title : 'Notes'" />

    <div v-if="props.notes && !props.note">
        <PageHeader
            title="Notes"
            description="Capture team knowledge and connect it to related records."
        />

        <div class="min-w-0 flex-1 px-4 py-6">
            <div class="mx-auto w-full max-w-7xl">
                <div class="mb-4 flex items-center justify-end gap-3">
                    <SearchInput
                        v-model="searchQuery"
                        data-testid="notes-search-input"
                        placeholder="Search notes..."
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

                    <InfiniteScroll data="notes" :buffer="1200">
                        <NoteList
                            :filtered-notes="props.notes.data"
                            :search-query="searchQuery"
                            :navigate-to-note="navigateToNote"
                            :open-delete-dialog="
                                (note) =>
                                    deleteDialogRef?.openDeleteDialog(note)
                            "
                            :open-create-dialog="openCreateDialog"
                            :view-mode="viewMode"
                        />
                    </InfiniteScroll>
                </div>
            </div>
        </div>
    </div>

    <NoteDetailOverlay
        v-if="props.note"
        :note="props.note"
        :record-links="props.recordLinks"
        :record-tags="props.recordTags"
        :activity-history="props.activityHistory"
        @close="closeNote"
        @saved="onSaved"
    />

    <DeleteNoteDialog ref="deleteDialogRef" />
</template>
