<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    GripVertical,
    MoreHorizontal,
    Trash2,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import SpreadsheetCell from '@/components/pages/spreadsheets/SpreadsheetCell.vue';
import SpreadsheetColumnDialog from '@/components/pages/spreadsheets/SpreadsheetColumnDialog.vue';
import SpreadsheetGridDeleteDialog from '@/components/pages/spreadsheets/SpreadsheetGridDeleteDialog.vue';
import SpreadsheetGridToolbar from '@/components/pages/spreadsheets/SpreadsheetGridToolbar.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { downloadSpreadsheetCsv } from '@/lib/spreadsheet-csv';
import {
    cloneSpreadsheetSnapshot,
    withSpreadsheetCell,
} from '@/lib/spreadsheet-snapshot';
import type {
    SpreadsheetCellValue,
    SpreadsheetColumn,
    SpreadsheetSnapshot,
} from '@/types/spreadsheets';

const props = defineProps<{
    filename: string;
}>();

const snapshot = defineModel<SpreadsheetSnapshot>({ required: true });

const sort = ref<{ columnId: string; direction: 'asc' | 'desc' } | null>(null);
const columnDialogOpen = ref(false);
const editingColumnId = ref<string | null>(null);
const draggingColumnId = ref<string | null>(null);
const pendingDeletion = ref<
    | { type: 'row'; id: string; label: string }
    | { type: 'column'; id: string; label: string }
    | null
>(null);

const visibleColumns = computed(() =>
    snapshot.value.columns.filter((column) => !column.hidden),
);
const tableWidth = computed(() =>
    visibleColumns.value.reduce(
        (total, column) => total + displayedColumnWidth(column),
        48,
    ),
);

const displayedRows = computed(() => {
    if (!sort.value) {
        return snapshot.value.rows;
    }

    const { columnId, direction } = sort.value;

    return [...snapshot.value.rows].sort((left, right) => {
        const comparison = compareValues(
            left.cells[columnId],
            right.cells[columnId],
        );

        return direction === 'asc' ? comparison : -comparison;
    });
});

const editingColumn = computed(
    () =>
        snapshot.value.columns.find(
            (column) => column.id === editingColumnId.value,
        ) ?? null,
);

function updateSnapshot(mutator: (draft: SpreadsheetSnapshot) => void): void {
    const draft = cloneSpreadsheetSnapshot(snapshot.value);
    mutator(draft);
    snapshot.value = draft;
}

function compareValues(
    left: SpreadsheetCellValue | undefined,
    right: SpreadsheetCellValue | undefined,
): number {
    if (left === right) {
        return 0;
    }

    if (left === null || left === undefined || left === '') {
        return 1;
    }

    if (right === null || right === undefined || right === '') {
        return -1;
    }

    if (typeof left === 'number' && typeof right === 'number') {
        return left - right;
    }

    return String(left).localeCompare(String(right), undefined, {
        numeric: true,
        sensitivity: 'base',
    });
}

function toggleSort(columnId: string): void {
    if (sort.value?.columnId !== columnId) {
        sort.value = { columnId, direction: 'asc' };

        return;
    }

    if (sort.value.direction === 'asc') {
        sort.value = { columnId, direction: 'desc' };

        return;
    }

    sort.value = null;
}

function updateCell(
    rowId: string,
    columnId: string,
    value: SpreadsheetCellValue,
): void {
    snapshot.value = withSpreadsheetCell(
        snapshot.value,
        rowId,
        columnId,
        value,
    );
}

function addRow(): void {
    updateSnapshot((draft) => {
        draft.rows.push({ id: crypto.randomUUID(), cells: {} });
    });
}

function removeRow(rowId: string): void {
    updateSnapshot((draft) => {
        draft.rows = draft.rows.filter((row) => row.id !== rowId);
    });
}

function requestRowDeletion(rowId: string, rowNumber: number): void {
    pendingDeletion.value = {
        type: 'row',
        id: rowId,
        label: `row ${rowNumber}`,
    };
}

function openAddColumn(): void {
    editingColumnId.value = null;
    columnDialogOpen.value = true;
}

function openEditColumn(columnId: string): void {
    editingColumnId.value = columnId;
    columnDialogOpen.value = true;
}

function saveColumn(
    values: Pick<SpreadsheetColumn, 'name' | 'type' | 'options'>,
): void {
    updateSnapshot((draft) => {
        const existing = draft.columns.find(
            (column) => column.id === editingColumnId.value,
        );

        if (existing) {
            Object.assign(existing, values);

            return;
        }

        draft.columns.push({
            id: crypto.randomUUID(),
            ...values,
            width: values.type === 'checkbox' ? 110 : 180,
            hidden: false,
        });
    });
}

function setColumnVisible(columnId: string, visible: boolean): void {
    if (!visible && visibleColumns.value.length === 1) {
        return;
    }

    updateSnapshot((draft) => {
        const column = draft.columns.find((item) => item.id === columnId);

        if (column) {
            column.hidden = !visible;
        }
    });
}

function removeColumn(columnId: string): void {
    if (snapshot.value.columns.length === 1) {
        return;
    }

    updateSnapshot((draft) => {
        draft.columns = draft.columns.filter(
            (column) => column.id !== columnId,
        );

        for (const row of draft.rows) {
            delete row.cells[columnId];
        }
    });

    if (sort.value?.columnId === columnId) {
        sort.value = null;
    }
}

function requestColumnDeletion(column: SpreadsheetColumn): void {
    if (snapshot.value.columns.length === 1) {
        return;
    }

    pendingDeletion.value = {
        type: 'column',
        id: column.id,
        label: `the "${column.name}" column`,
    };
}

function confirmDeletion(): void {
    const deletion = pendingDeletion.value;
    pendingDeletion.value = null;

    if (!deletion) {
        return;
    }

    if (deletion.type === 'row') {
        removeRow(deletion.id);

        return;
    }

    removeColumn(deletion.id);
}

function startColumnDrag(columnId: string): void {
    draggingColumnId.value = columnId;
}

function dropColumn(targetColumnId: string): void {
    const sourceColumnId = draggingColumnId.value;
    draggingColumnId.value = null;

    if (!sourceColumnId || sourceColumnId === targetColumnId) {
        return;
    }

    updateSnapshot((draft) => {
        const sourceIndex = draft.columns.findIndex(
            (column) => column.id === sourceColumnId,
        );
        const targetIndex = draft.columns.findIndex(
            (column) => column.id === targetColumnId,
        );

        if (sourceIndex < 0 || targetIndex < 0) {
            return;
        }

        const [column] = draft.columns.splice(sourceIndex, 1);

        if (column) {
            draft.columns.splice(targetIndex, 0, column);
        }
    });
}

const resizingColumnId = ref<string | null>(null);
const resizeCurrentWidth = ref(0);
let resizeStartX = 0;
let resizeStartWidth = 0;

function displayedColumnWidth(column: SpreadsheetColumn): number {
    return resizingColumnId.value === column.id
        ? resizeCurrentWidth.value
        : column.width;
}

function startResize(event: PointerEvent, column: SpreadsheetColumn): void {
    event.preventDefault();
    event.stopPropagation();
    resizingColumnId.value = column.id;
    resizeStartX = event.clientX;
    resizeStartWidth = column.width;
    resizeCurrentWidth.value = column.width;
    window.addEventListener('pointermove', resizeColumn);
    window.addEventListener('pointerup', stopResize, { once: true });
    window.addEventListener('pointercancel', stopResize, { once: true });
    window.addEventListener('blur', stopResize, { once: true });
}

function resizeColumn(event: PointerEvent): void {
    if (!resizingColumnId.value) {
        return;
    }

    resizeCurrentWidth.value = Math.min(
        500,
        Math.max(80, resizeStartWidth + event.clientX - resizeStartX),
    );
}

function stopResize(): void {
    const columnId = resizingColumnId.value;
    const width = resizeCurrentWidth.value;

    cleanupResize();

    if (!columnId) {
        return;
    }

    updateSnapshot((draft) => {
        const column = draft.columns.find((item) => item.id === columnId);

        if (column) {
            column.width = width;
        }
    });
}

function cleanupResize(): void {
    resizingColumnId.value = null;
    window.removeEventListener('pointermove', resizeColumn);
    window.removeEventListener('pointerup', stopResize);
    window.removeEventListener('pointercancel', stopResize);
    window.removeEventListener('blur', stopResize);
}

onBeforeUnmount(cleanupResize);

function exportCsv(): void {
    downloadSpreadsheetCsv(
        props.filename,
        visibleColumns.value,
        displayedRows.value,
    );
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-xs">
        <SpreadsheetGridToolbar
            :columns="snapshot.columns"
            :row-count="snapshot.rows.length"
            :visible-column-count="visibleColumns.length"
            @add-row="addRow"
            @add-column="openAddColumn"
            @export-csv="exportCsv"
            @set-column-visible="setColumnVisible"
        />

        <div class="relative max-h-[calc(100svh-18rem)] overflow-auto">
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-x-0 top-0 h-11 border-b bg-muted"
            />
            <table
                class="relative table-fixed border-separate border-spacing-0 text-sm"
                :style="{ width: `${tableWidth}px` }"
            >
                <colgroup>
                    <col class="w-12" />
                    <col
                        v-for="column in visibleColumns"
                        :key="column.id"
                        :style="{ width: `${displayedColumnWidth(column)}px` }"
                    />
                </colgroup>
                <thead class="sticky top-0 z-10 bg-muted">
                    <tr>
                        <th
                            class="sticky left-0 z-20 h-11 border-r border-b bg-muted text-center text-xs font-medium text-muted-foreground"
                        >
                            #
                        </th>
                        <th
                            v-for="column in visibleColumns"
                            :key="column.id"
                            :draggable="resizingColumnId === null"
                            class="group relative h-11 border-r border-b text-left font-medium select-none"
                            @dragstart="startColumnDrag(column.id)"
                            @dragend="draggingColumnId = null"
                            @dragover.prevent
                            @drop.prevent="dropColumn(column.id)"
                        >
                            <div class="flex h-full items-center gap-1 px-2">
                                <GripVertical
                                    class="h-3.5 w-3.5 shrink-0 cursor-grab text-muted-foreground/60 opacity-0 group-hover:opacity-100"
                                />
                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-center gap-1.5 text-left"
                                    :title="`Sort by ${column.name}`"
                                    @click="toggleSort(column.id)"
                                >
                                    <span class="truncate">{{
                                        column.name
                                    }}</span>
                                    <ArrowUp
                                        v-if="
                                            sort?.columnId === column.id &&
                                            sort.direction === 'asc'
                                        "
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <ArrowDown
                                        v-else-if="sort?.columnId === column.id"
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="h-3.5 w-3.5 shrink-0 text-muted-foreground/50"
                                    />
                                </button>

                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            type="button"
                                            class="rounded p-1 text-muted-foreground opacity-0 group-hover:opacity-100 hover:bg-accent hover:text-foreground data-[state=open]:opacity-100"
                                            :aria-label="`${column.name} options`"
                                        >
                                            <MoreHorizontal class="h-4 w-4" />
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            @select="openEditColumn(column.id)"
                                        >
                                            Edit column
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            :disabled="
                                                visibleColumns.length === 1
                                            "
                                            @select="
                                                setColumnVisible(
                                                    column.id,
                                                    false,
                                                )
                                            "
                                        >
                                            Hide column
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            class="text-destructive focus:text-destructive"
                                            :disabled="
                                                snapshot.columns.length === 1
                                            "
                                            @select="
                                                requestColumnDeletion(column)
                                            "
                                        >
                                            Delete column
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <button
                                type="button"
                                class="absolute top-0 -right-1 z-10 h-full w-2 cursor-col-resize touch-none"
                                :aria-label="`Resize ${column.name}`"
                                @pointerdown="startResize($event, column)"
                            >
                                <span
                                    class="mx-auto block h-full w-px bg-border group-hover:bg-primary/50"
                                />
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, rowIndex) in displayedRows"
                        :key="row.id"
                        class="group/row hover:bg-muted/20"
                    >
                        <th
                            class="group/row-number sticky left-0 z-[5] h-10 border-r border-b bg-card text-center text-xs font-normal text-muted-foreground group-hover/row:bg-muted/30"
                        >
                            <span
                                :data-testid="`spreadsheet-row-number-${rowIndex + 1}`"
                                class="group-focus-within/row-number:opacity-0 group-hover/row:opacity-0"
                            >
                                {{ rowIndex + 1 }}
                            </span>
                            <button
                                type="button"
                                class="absolute inset-0 flex cursor-pointer items-center justify-center text-muted-foreground opacity-0 group-hover/row:opacity-100 hover:text-destructive focus:opacity-100 focus-visible:outline-none"
                                :aria-label="`Delete row ${rowIndex + 1}`"
                                @click="
                                    requestRowDeletion(row.id, rowIndex + 1)
                                "
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </th>
                        <td
                            v-for="column in visibleColumns"
                            :key="column.id"
                            class="h-10 border-r border-b p-0"
                        >
                            <SpreadsheetCell
                                :row="row"
                                :column="column"
                                :row-number="rowIndex + 1"
                                @update="updateCell(row.id, column.id, $event)"
                            />
                        </td>
                    </tr>
                    <tr v-if="displayedRows.length === 0">
                        <td
                            :colspan="visibleColumns.length + 1"
                            class="h-32 text-center text-sm text-muted-foreground"
                        >
                            No rows yet.
                            <button
                                class="font-medium text-primary"
                                @click="addRow"
                            >
                                Add a row
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <SpreadsheetColumnDialog
        v-model:open="columnDialogOpen"
        :column="editingColumn"
        @save="saveColumn"
    />

    <SpreadsheetGridDeleteDialog
        :open="pendingDeletion !== null"
        :kind="pendingDeletion?.type ?? 'row'"
        :label="pendingDeletion?.label ?? 'this item'"
        @update:open="$event || (pendingDeletion = null)"
        @confirm="confirmDeletion"
    />
</template>
