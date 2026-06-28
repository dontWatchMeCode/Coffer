<script setup lang="ts">
import {
    Check,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
} from 'lucide-vue-next';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
    ComboboxVirtualizer,
    useFilter,
} from 'reka-ui';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    currentMonth: number;
    currentYear: number;
    months: string[];
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:currentMonth': [value: number];
    'update:currentYear': [value: number];
}>();

const minYear = 1900;
const maxYear = 2999;

const yearRange = Array.from(
    { length: maxYear - minYear + 1 },
    (_, index) => minYear + index,
);

const isYearPickerOpen = ref(false);
const yearSearch = ref('');
const { contains } = useFilter({ sensitivity: 'base' });

const effectiveYearSearch = computed(() => {
    const value = yearSearch.value.trim();

    if (value === String(props.currentYear) && value.length > 1) {
        return value.slice(0, -1);
    }

    return value;
});

const filteredYears = computed(() =>
    yearRange.filter((year) =>
        contains(String(year), effectiveYearSearch.value),
    ),
);

function displayYearValue(value: number | undefined): string {
    return String(value ?? props.currentYear);
}

function updateCurrentYear(value: unknown): void {
    if (
        typeof value === 'number' &&
        Number.isInteger(value) &&
        value >= minYear &&
        value <= maxYear
    ) {
        emit('update:currentYear', value);
    }
}

function updateYearPickerOpen(isOpen: boolean): void {
    isYearPickerOpen.value = isOpen;

    if (!isOpen) {
        yearSearch.value = '';
    }
}

function prevMonth(): void {
    if (props.currentMonth === 0 && props.currentYear === minYear) {
        return;
    }

    if (props.currentMonth === 0) {
        emit('update:currentMonth', 11);
        emit('update:currentYear', props.currentYear - 1);
    } else {
        emit('update:currentMonth', props.currentMonth - 1);
    }
}

function nextMonth(): void {
    if (props.currentMonth === 11 && props.currentYear === maxYear) {
        return;
    }

    if (props.currentMonth === 11) {
        emit('update:currentMonth', 0);
        emit('update:currentYear', props.currentYear + 1);
    } else {
        emit('update:currentMonth', props.currentMonth + 1);
    }
}

function goToday(): void {
    const today = new Date();

    emit('update:currentMonth', today.getMonth());
    emit('update:currentYear', today.getFullYear());
}
</script>

<template>
    <div class="flex items-center gap-2">
        <div class="flex items-center rounded-md border bg-muted p-0.5">
            <Button
                variant="ghost"
                size="icon-sm"
                class="cursor-pointer hover:bg-background!"
                @click="prevMonth"
            >
                <ChevronLeft class="h-3.5 w-3.5" />
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="h-7 cursor-pointer hover:bg-background!"
                @click="goToday"
            >
                Today
            </Button>
            <Button
                variant="ghost"
                size="icon-sm"
                class="cursor-pointer hover:bg-background!"
                @click="nextMonth"
            >
                <ChevronRight class="h-3.5 w-3.5" />
            </Button>
        </div>

        <Select
            :model-value="String(currentMonth)"
            @update:model-value="emit('update:currentMonth', Number($event))"
        >
            <SelectTrigger
                class="ml-1 h-7 w-auto gap-4 border-none bg-transparent px-1 text-sm font-semibold shadow-none hover:bg-transparent focus:ring-0 dark:bg-transparent dark:hover:bg-transparent"
            >
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="(month, idx) in months"
                    :key="idx"
                    :value="String(idx)"
                >
                    {{ month }}
                </SelectItem>
            </SelectContent>
        </Select>

        <ComboboxRoot
            :model-value="currentYear"
            @update:model-value="updateCurrentYear"
            :open="isYearPickerOpen"
            @update:open="updateYearPickerOpen"
            :ignore-filter="true"
            open-on-focus
            class="min-w-0"
        >
            <ComboboxAnchor
                class="flex h-7 items-center gap-1 rounded-md border-none bg-transparent px-1 text-sm font-semibold shadow-none"
            >
                <ComboboxInput
                    v-model="yearSearch"
                    :display-value="displayYearValue"
                    placeholder="Year"
                    class="h-full w-14 bg-transparent text-center outline-none placeholder:text-foreground"
                />
                <ComboboxTrigger class="cursor-pointer">
                    <ChevronsUpDown class="size-3.5 shrink-0 opacity-50" />
                </ComboboxTrigger>
            </ComboboxAnchor>

            <ComboboxPortal>
                <ComboboxContent
                    position="popper"
                    class="z-50 max-h-64 min-w-[var(--reka-combobox-trigger-width)] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95"
                    :side-offset="4"
                >
                    <ComboboxViewport class="h-56 p-1">
                        <ComboboxEmpty
                            class="px-2 py-3 text-sm text-muted-foreground"
                        >
                            No years found.
                        </ComboboxEmpty>

                        <ComboboxVirtualizer
                            v-slot="{ option }"
                            :options="filteredYears"
                            :text-content="(value) => String(value)"
                            :estimate-size="30"
                        >
                            <ComboboxItem
                                :value="option"
                                class="relative flex w-full items-center rounded-sm py-1.5 pr-8 pl-2 text-sm outline-hidden select-none focus:bg-accent focus:text-accent-foreground data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                            >
                                {{ option }}

                                <span
                                    class="absolute right-2 flex size-3.5 items-center justify-center"
                                >
                                    <ComboboxItemIndicator>
                                        <Check class="size-4" />
                                    </ComboboxItemIndicator>
                                </span>
                            </ComboboxItem>
                        </ComboboxVirtualizer>
                    </ComboboxViewport>
                </ComboboxContent>
            </ComboboxPortal>
        </ComboboxRoot>
    </div>
</template>
