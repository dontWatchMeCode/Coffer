<script setup lang="ts">
import type { AcceptableValue } from 'reka-ui';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    SpreadsheetCellValue,
    SpreadsheetColumn,
    SpreadsheetRow,
} from '@/types/spreadsheets';

const props = defineProps<{
    row: SpreadsheetRow;
    column: SpreadsheetColumn;
    rowNumber: number;
}>();

const emit = defineEmits<{
    update: [value: SpreadsheetCellValue];
}>();

function onInput(event: Event): void {
    const input = event.currentTarget as HTMLInputElement;

    emit(
        'update',
        props.column.type === 'number'
            ? input.value === ''
                ? null
                : Number(input.value)
            : input.value,
    );
}

function onSelect(value: AcceptableValue): void {
    if (typeof value === 'string') {
        emit('update', value);
    } else if (value === null) {
        emit('update', null);
    }
}

function selectValue(value: SpreadsheetCellValue | undefined): string | null {
    return typeof value === 'string' && value !== '' ? value : null;
}
</script>

<template>
    <div class="flex h-full w-full items-center">
        <Checkbox
            v-if="column.type === 'checkbox'"
            class="mx-auto"
            :model-value="Boolean(row.cells[column.id])"
            :aria-label="`${column.name}, row ${rowNumber}`"
            @update:model-value="emit('update', $event === true)"
        />
        <Select
            v-else-if="column.type === 'select'"
            :model-value="selectValue(row.cells[column.id])"
            @update:model-value="onSelect"
        >
            <SelectTrigger
                class="h-full w-full rounded-none border-0 bg-transparent px-2 shadow-none hover:bg-transparent focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset dark:bg-transparent dark:hover:bg-transparent"
                :aria-label="`${column.name}, row ${rowNumber}`"
            >
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem :value="null">None</SelectItem>
                <SelectItem
                    v-for="option in column.options"
                    :key="option"
                    :value="option"
                >
                    {{ option }}
                </SelectItem>
            </SelectContent>
        </Select>
        <input
            v-else
            :type="column.type"
            class="h-full w-full bg-transparent px-2 outline-none focus:bg-background focus:ring-2 focus:ring-ring focus:ring-inset"
            :value="row.cells[column.id] ?? ''"
            :aria-label="`${column.name}, row ${rowNumber}`"
            @input="onInput"
        />
    </div>
</template>
