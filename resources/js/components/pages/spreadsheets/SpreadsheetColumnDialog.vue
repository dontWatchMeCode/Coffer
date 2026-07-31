<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type {
    SpreadsheetColumn,
    SpreadsheetColumnType,
} from '@/types/spreadsheets';

const props = defineProps<{
    open: boolean;
    column?: SpreadsheetColumn | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    save: [column: Pick<SpreadsheetColumn, 'name' | 'type' | 'options'>];
}>();

const name = ref('');
const type = ref<SpreadsheetColumnType>('text');
const options = ref('');

const title = computed(() => (props.column ? 'Edit column' : 'Add column'));
const canSave = computed(() => name.value.trim() !== '');

watch(
    () => [props.open, props.column] as const,
    ([open, column]) => {
        if (!open) {
            return;
        }

        name.value = column?.name ?? '';
        type.value = column?.type ?? 'text';
        options.value = column?.options.join(', ') ?? '';
    },
    { immediate: true },
);

function save(): void {
    if (!canSave.value) {
        return;
    }

    emit('save', {
        name: name.value.trim(),
        type: type.value,
        options:
            type.value === 'select'
                ? options.value
                      .split(',')
                      .map((option) => option.trim())
                      .filter(
                          (option, index, all) =>
                              Boolean(option) && all.indexOf(option) === index,
                      )
                      .slice(0, 50)
                : [],
    });
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    Choose how values in this column are entered.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="save">
                <label class="grid gap-1.5 text-sm font-medium">
                    Name
                    <Input v-model="name" autofocus maxlength="100" />
                </label>

                <label class="grid gap-1.5 text-sm font-medium">
                    Type
                    <select
                        v-model="type"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus:border-ring focus:ring-3 focus:ring-ring/50"
                    >
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="select">Select</option>
                        <option value="checkbox">Checkbox</option>
                    </select>
                </label>

                <label
                    v-if="type === 'select'"
                    class="grid gap-1.5 text-sm font-medium"
                >
                    Options
                    <Input
                        v-model="options"
                        placeholder="Not started, In progress, Done"
                    />
                    <span class="text-xs font-normal text-muted-foreground">
                        Separate options with commas.
                    </span>
                </label>
            </form>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    Cancel
                </Button>
                <Button type="button" :disabled="!canSave" @click="save">
                    {{ props.column ? 'Save column' : 'Add column' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
