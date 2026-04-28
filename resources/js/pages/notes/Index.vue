<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { FileText, ListPlus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateNoteDialog from '@/components/pages/notes/CreateNoteDialog.vue';
import DeleteNoteDialog from '@/components/pages/notes/DeleteNoteDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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

function formatDate(value?: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '';
}

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
    >
        <template #actions>
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
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <div class="relative w-full max-w-sm">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        :model-value="searchQuery"
                        placeholder="Search notes..."
                        class="pl-9"
                        @update:model-value="searchQuery = String($event)"
                    />
                </div>
            </div>

            <div
                v-if="filteredNotes.length > 0"
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <Card
                    v-for="note in filteredNotes"
                    :key="note.id"
                    class="group cursor-pointer transition-colors hover:bg-accent/50"
                    @click="navigateToNote(note)"
                >
                    <CardHeader class="gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-muted"
                            >
                                <FileText
                                    class="h-5 w-5 text-muted-foreground"
                                />
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100"
                                @click.stop="
                                    deleteDialogRef?.openDeleteDialog(note)
                                "
                            >
                                <Trash2 class="h-4 w-4 text-muted-foreground" />
                            </Button>
                        </div>
                        <CardTitle class="line-clamp-2 text-base">
                            {{ note.title }}
                        </CardTitle>
                    </CardHeader>

                    <CardContent>
                        <p
                            v-if="note.excerpt"
                            class="line-clamp-4 text-sm text-muted-foreground"
                        >
                            {{ note.excerpt }}
                        </p>
                        <p v-else class="text-sm text-muted-foreground italic">
                            No body yet.
                        </p>
                    </CardContent>

                    <CardFooter class="flex-col items-start gap-3">
                        <div
                            v-if="note.tags.length"
                            class="flex flex-wrap gap-1"
                        >
                            <Badge
                                v-for="tag in note.tags.slice(0, 4)"
                                :key="tag.id"
                                variant="secondary"
                                class="text-[11px]"
                            >
                                {{ tag.name }}
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Updated {{ formatDate(note.updatedAt) }}
                        </p>
                    </CardFooter>
                </Card>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center"
            >
                <FileText class="mb-3 h-12 w-12 text-muted-foreground/50" />
                <p class="font-medium">
                    {{
                        searchQuery
                            ? 'No notes match your search.'
                            : 'No notes yet.'
                    }}
                </p>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    {{
                        searchQuery
                            ? 'Try another title, body, or tag.'
                            : 'Create your first note to capture team context.'
                    }}
                </p>
                <Button
                    v-if="!searchQuery"
                    variant="outline"
                    size="sm"
                    class="mt-4 cursor-pointer"
                    @click="openCreateDialog"
                >
                    <ListPlus class="mr-1.5 h-3.5 w-3.5" />
                    Add your first note
                </Button>
            </div>
        </div>
    </div>

    <DeleteNoteDialog ref="deleteDialogRef" />
</template>
