<script setup lang="ts">
import { Columns3, Download, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { SpreadsheetColumn } from '@/types/spreadsheets';

defineProps<{
    columns: SpreadsheetColumn[];
    rowCount: number;
    visibleColumnCount: number;
}>();

const emit = defineEmits<{
    addRow: [];
    addColumn: [];
    exportCsv: [];
    setColumnVisible: [columnId: string, visible: boolean];
}>();
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/20 px-3 py-2"
    >
        <div class="flex items-center gap-1 text-xs text-muted-foreground">
            <span>{{ rowCount }} rows</span>
            <span aria-hidden="true">·</span>
            <span>{{ columns.length }} columns</span>
        </div>

        <div class="flex items-center gap-1">
            <Button
                type="button"
                size="sm"
                variant="ghost"
                @click="emit('addRow')"
            >
                <Plus class="h-4 w-4" />
                Row
            </Button>
            <Button
                type="button"
                size="sm"
                variant="ghost"
                @click="emit('addColumn')"
            >
                <Plus class="h-4 w-4" />
                Column
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        title="Columns"
                    >
                        <Columns3 class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-52">
                    <DropdownMenuLabel>Visible columns</DropdownMenuLabel>
                    <DropdownMenuItem
                        v-for="column in columns"
                        :key="column.id"
                        :disabled="!column.hidden && visibleColumnCount === 1"
                        @select.prevent="
                            emit('setColumnVisible', column.id, column.hidden)
                        "
                    >
                        <span
                            class="flex size-4 items-center justify-center rounded border text-[10px]"
                            :class="
                                !column.hidden
                                    ? 'bg-primary text-primary-foreground'
                                    : ''
                            "
                        >
                            {{ !column.hidden ? '✓' : '' }}
                        </span>
                        <span class="truncate">{{ column.name }}</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Button
                type="button"
                size="icon"
                variant="ghost"
                title="Export CSV"
                @click="emit('exportCsv')"
            >
                <Download class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
