<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { FileSpreadsheet, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import SearchInput from '@/components/list/SearchInput.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import SpreadsheetGrid from '@/components/pages/spreadsheets/SpreadsheetGrid.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSearch } from '@/composables/useSearch';
import { cloneSpreadsheetSnapshot } from '@/lib/spreadsheet-snapshot';
import { formatDate } from '@/lib/utils';
import { destroy, index, show, store, trash } from '@/routes/team/spreadsheets';
import { update as saveWorkbook } from '@/routes/team/spreadsheets/workbook';
import type {
    ActivityHistoryConfig,
    PaginatedData,
    SpreadsheetSnapshot,
    SpreadsheetWorkbook,
    Team,
} from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    spreadsheets?: PaginatedData<SpreadsheetWorkbook>;
    spreadsheet?: SpreadsheetWorkbook;
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
const items = computed(() => props.spreadsheets?.data ?? []);
const newTitle = ref('');
const editTitle = ref(props.spreadsheet?.title ?? '');
const workbook = ref<SpreadsheetSnapshot>(
    cloneSpreadsheetSnapshot(
        props.spreadsheet?.snapshot ?? {
            version: 1,
            columns: [],
            rows: [],
        },
    ),
);
const savedWorkbook = ref(JSON.stringify(workbook.value));
const saving = ref(false);
const saveState = ref<'idle' | 'error'>('idle');
const deleteDialogOpen = ref(false);
const spreadsheetToDelete = ref<SpreadsheetWorkbook | null>(null);

const { searchQuery } = useSearch(
    index(currentTeamSlug.value).url,
    'spreadsheets',
);

const isDirty = computed(
    () =>
        Boolean(props.spreadsheet) &&
        (editTitle.value.trim() !== props.spreadsheet?.title ||
            JSON.stringify(workbook.value) !== savedWorkbook.value),
);

watch(
    () => props.spreadsheet,
    (spreadsheet) => {
        if (!spreadsheet || saving.value) {
            return;
        }

        editTitle.value = spreadsheet.title;

        if (spreadsheet.snapshot) {
            workbook.value = cloneSpreadsheetSnapshot(spreadsheet.snapshot);
            savedWorkbook.value = JSON.stringify(spreadsheet.snapshot);
        }
    },
);

function createSpreadsheet(): void {
    const title = newTitle.value.trim() || 'Untitled spreadsheet';

    router.post(
        store(currentTeamSlug.value).url,
        { title },
        {
            onSuccess: () => {
                newTitle.value = '';
            },
        },
    );
}

function openSpreadsheet(spreadsheet: SpreadsheetWorkbook): void {
    router.visit(
        show({
            current_team: currentTeamSlug.value,
            spreadsheet: spreadsheet.id,
        }).url,
    );
}

function closeSpreadsheet(): void {
    if (isDirty.value && !confirm('Discard unsaved spreadsheet changes?')) {
        return;
    }

    router.visit(index(currentTeamSlug.value).url);
}

function saveChanges(): void {
    if (
        !props.spreadsheet ||
        saving.value ||
        !editTitle.value.trim() ||
        !isDirty.value
    ) {
        return;
    }

    saving.value = true;
    saveState.value = 'idle';
    const submittedTitle = editTitle.value.trim();
    const submittedSnapshot = cloneSpreadsheetSnapshot(workbook.value);
    const submittedWorkbook = JSON.stringify(submittedSnapshot);

    router.patch(
        saveWorkbook({
            current_team: currentTeamSlug.value,
            spreadsheet: props.spreadsheet.id,
        }).url,
        {
            title: submittedTitle,
            snapshot: submittedSnapshot,
        },
        {
            only: ['spreadsheet', 'activityHistory'],
            preserveScroll: true,
            onSuccess: () => {
                savedWorkbook.value = submittedWorkbook;
                saveState.value = 'idle';
            },
            onError: () => {
                saveState.value = 'error';
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function openDeleteDialog(spreadsheet: SpreadsheetWorkbook): void {
    spreadsheetToDelete.value = spreadsheet;
    deleteDialogOpen.value = true;
}

function deleteSpreadsheet(): void {
    const spreadsheet = spreadsheetToDelete.value;

    if (!spreadsheet) {
        return;
    }

    deleteDialogOpen.value = false;
    spreadsheetToDelete.value = null;
    router.delete(
        destroy({
            current_team: currentTeamSlug.value,
            spreadsheet: spreadsheet.id,
        }).url,
    );
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: {
        currentTeam?: Team | null;
        spreadsheet?: SpreadsheetWorkbook;
    }) => ({
        breadcrumbs: [
            {
                title: 'Spreadsheets',
                href: index(pageProps.currentTeam?.slug).url,
            },
            ...(pageProps.spreadsheet
                ? [{ title: pageProps.spreadsheet.title }]
                : []),
        ],
    }),
});
</script>

<template>
    <Head :title="props.spreadsheet?.title ?? 'Spreadsheets'" />

    <div v-if="!props.spreadsheet">
        <PageHeader
            title="Spreadsheets"
            description="Flexible tables for team data."
        />

        <div class="min-w-0 flex-1 px-4 py-6">
            <div class="mx-auto w-full max-w-7xl space-y-4">
                <div
                    class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                >
                    <SearchInput
                        v-model="searchQuery"
                        data-testid="spreadsheets-search-input"
                        placeholder="Search spreadsheets..."
                    />

                    <div class="flex items-center justify-end gap-2">
                        <Input
                            v-model="newTitle"
                            class="max-w-64"
                            placeholder="Spreadsheet name"
                            @keydown.enter.prevent="createSpreadsheet"
                        />
                        <Button size="sm" @click="createSpreadsheet">
                            <Plus class="h-4 w-4" />
                            New
                        </Button>
                        <Button
                            size="icon"
                            variant="outline"
                            title="Trash"
                            as-child
                        >
                            <Link :href="trash(currentTeamSlug).url">
                                <Trash2 class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </div>

                <InfiniteScroll v-if="props.spreadsheets" data="spreadsheets">
                    <ListContainer v-if="items.length > 0">
                        <ListItem
                            v-for="item in items"
                            :key="item.id"
                            :data-testid="`spreadsheet-card-${item.id}`"
                            @click="openSpreadsheet(item)"
                        >
                            <div class="flex flex-col gap-3">
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <ListItemIcon>
                                        <FileSpreadsheet
                                            class="h-5 w-5 text-muted-foreground"
                                        />
                                    </ListItemIcon>
                                    <ListItemActions
                                        :data-testid="`spreadsheet-actions-${item.id}`"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8"
                                            aria-label="Delete spreadsheet"
                                            @click.stop="openDeleteDialog(item)"
                                        >
                                            <Trash2
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </Button>
                                    </ListItemActions>
                                </div>

                                <p class="line-clamp-2 text-base font-medium">
                                    {{ item.title }}
                                </p>

                                <p class="text-sm text-muted-foreground">
                                    {{ item.rowCount }} rows ·
                                    {{ item.columnCount }} columns
                                </p>

                                <div class="mt-auto flex flex-col gap-3">
                                    <div
                                        v-if="item.tags.length"
                                        class="flex flex-wrap gap-1"
                                    >
                                        <Badge
                                            v-for="tag in item.tags.slice(0, 4)"
                                            :key="tag.id"
                                            variant="secondary"
                                            class="text-[11px]"
                                        >
                                            {{ tag.name }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Updated {{ formatDate(item.updatedAt) }}
                                    </p>
                                </div>
                            </div>
                        </ListItem>
                    </ListContainer>

                    <EmptyState
                        v-else
                        :title="
                            searchQuery
                                ? 'No spreadsheets match your search.'
                                : 'No spreadsheets yet.'
                        "
                        :description="
                            searchQuery
                                ? 'Try another title or tag.'
                                : 'Create one to start organizing team data.'
                        "
                        :show-action="!searchQuery"
                        action-label="Add your first spreadsheet"
                        @action="createSpreadsheet"
                    >
                        <template #icon>
                            <FileSpreadsheet class="h-12 w-12" />
                        </template>
                        <template #action-icon>
                            <Plus class="mr-1.5 h-3.5 w-3.5" />
                        </template>
                    </EmptyState>
                </InfiniteScroll>
            </div>
        </div>
    </div>

    <div v-else>
        <PageHeader
            :title="props.spreadsheet.title"
            description="Edit cells directly, then save your changes."
            back-label="Back to spreadsheets"
            :back-handler="closeSpreadsheet"
        />

        <div class="min-w-0 flex-1 px-4 py-6">
            <EditorSidebarLayout
                :updated-at="props.spreadsheet.updatedAt"
                :on-save="saveChanges"
                :save-disabled="!isDirty || saving || !editTitle.trim()"
                :save-label="saving ? 'Saving...' : 'Save changes'"
                :on-delete="() => openDeleteDialog(props.spreadsheet!)"
                delete-label="Delete spreadsheet"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #main>
                    <div class="space-y-3">
                        <Input
                            v-model="editTitle"
                            class="h-10 max-w-md text-base font-semibold"
                            maxlength="255"
                            aria-label="Spreadsheet title"
                        />
                        <p
                            v-if="saveState === 'error'"
                            class="text-xs text-destructive"
                            role="alert"
                        >
                            Save failed. Check the spreadsheet data and try
                            again.
                        </p>
                        <SpreadsheetGrid
                            v-model="workbook"
                            :filename="editTitle"
                        />
                    </div>
                </template>

                <template #sidebar-top>
                    <ActivityHistoryPanel
                        v-if="activityHistory"
                        :config="activityHistory"
                        :team-slug="currentTeamSlug"
                    />
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <ConfirmDeleteDialog
        v-model:open="deleteDialogOpen"
        title="Delete spreadsheet"
        :description="`${spreadsheetToDelete?.title ?? 'This spreadsheet'} will move to trash and can be restored later.`"
        confirm-label="Move to trash"
        :confirm-icon="Trash2"
        @confirm="deleteSpreadsheet"
    />
</template>
