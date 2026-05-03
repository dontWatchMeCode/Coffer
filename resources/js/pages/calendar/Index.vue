<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    Check,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    List,
    ListPlus,
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
import PageHeader from '@/components/page/PageHeader.vue';
import CalendarEventDialogs from '@/components/pages/calendar/CalendarEventDialogs.vue';
import CalendarGrid from '@/components/pages/calendar/CalendarGrid.vue';
import CalendarList from '@/components/pages/calendar/CalendarList.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as calendarIndex } from '@/routes/team/calendar';
import { edit as editEventRoute } from '@/routes/team/calendar/events';
import type { CalendarEventItem, Team } from '@/types';

type Props = {
    events: CalendarEventItem[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Calendar',
                href: calendarIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});

const now = new Date();
const currentYear = ref(now.getFullYear());
const currentMonth = ref(now.getMonth());

const months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

type ViewMode = 'calendar' | 'list';

const viewMode = ref<ViewMode>('list');
const isYearPickerOpen = ref(false);

const minYear = 1900;
const maxYear = 2999;

const yearRange = Array.from(
    { length: maxYear - minYear + 1 },
    (_, index) => minYear + index,
);

const yearSearch = ref('');
const { contains } = useFilter({ sensitivity: 'base' });

const effectiveYearSearch = computed(() => {
    const value = yearSearch.value.trim();

    if (value === String(currentYear.value) && value.length > 1) {
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
    return String(value ?? currentYear.value);
}

function updateCurrentYear(value: unknown): void {
    if (
        typeof value === 'number' &&
        Number.isInteger(value) &&
        value >= minYear &&
        value <= maxYear
    ) {
        currentYear.value = value;
    }
}

function updateYearPickerOpen(isOpen: boolean): void {
    isYearPickerOpen.value = isOpen;

    if (!isOpen) {
        yearSearch.value = '';
    }
}

function daysInMonth(year: number, month: number): number {
    return new Date(year, month + 1, 0).getDate();
}

function firstDayOfMonth(year: number, month: number): number {
    return new Date(year, month, 1).getDay();
}

const calendarDays = computed(() => {
    const days = daysInMonth(currentYear.value, currentMonth.value);
    const startDay = firstDayOfMonth(currentYear.value, currentMonth.value);
    const cells: (number | null)[] = [];

    for (let i = 0; i < startDay; i++) {
        cells.push(null);
    }

    for (let i = 1; i <= days; i++) {
        cells.push(i);
    }

    return cells;
});

function formatDateStr(year: number, month: number, day: number): string {
    const m = String(month + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');

    return `${year}-${m}-${d}`;
}

const today = computed(() => {
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
});

function eventsForDay(day: number | null): CalendarEventItem[] {
    if (day === null) {
        return [];
    }

    const dateStr = formatDateStr(currentYear.value, currentMonth.value, day);

    return props.events.filter((e) => e.date === dateStr);
}

const futureEvents = computed(() =>
    props.events
        .filter((e) => e.date && e.date >= today.value)
        .sort((a, b) =>
            `${a.date ?? ''} ${a.time ?? ''}`.localeCompare(
                `${b.date ?? ''} ${b.time ?? ''}`,
            ),
        ),
);

function prevMonth(): void {
    if (currentMonth.value === 0 && currentYear.value === minYear) {
        return;
    }

    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
}

function nextMonth(): void {
    if (currentMonth.value === 11 && currentYear.value === maxYear) {
        return;
    }

    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
}

function goToday(): void {
    currentYear.value = now.getFullYear();
    currentMonth.value = now.getMonth();
}

const dialogsRef = ref<InstanceType<typeof CalendarEventDialogs> | null>(null);

function openCreateDialog(day: number): void {
    const dateStr = formatDateStr(currentYear.value, currentMonth.value, day);
    dialogsRef.value?.openCreateDialog(dateStr);
}

function openEditDialog(event: CalendarEventItem): void {
    router.visit(
        editEventRoute({
            current_team: currentTeamSlug.value,
            event: event.id,
        }).url,
    );
}
</script>

<template>
    <Head title="Calendar" />

    <PageHeader title="Calendar" description="View and manage team events.">
        <template #actions>
            <Button
                size="icon"
                title="Create event"
                class="cursor-pointer"
                @click="dialogsRef?.openCreateDialogNoDate()"
            >
                <ListPlus class="h-4 w-4" />
            </Button>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-4 flex items-center justify-between">
                <div
                    v-if="viewMode === 'calendar'"
                    class="flex items-center gap-2"
                >
                    <div
                        class="flex items-center rounded-md border bg-muted p-0.5"
                    >
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
                        @update:model-value="currentMonth = Number($event)"
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
                                <ChevronsUpDown
                                    class="size-3.5 shrink-0 opacity-50"
                                />
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

                <div
                    class="ml-auto flex items-center gap-1 rounded-lg border bg-muted p-0.5"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="{
                            'bg-background': viewMode === 'calendar',
                        }"
                        class="cursor-pointer hover:bg-background!"
                        @click="viewMode = 'calendar'"
                    >
                        <CalendarDays class="mr-1.5 h-3.5 w-3.5" />
                        Calendar
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="{
                            'bg-background': viewMode === 'list',
                        }"
                        class="cursor-pointer hover:bg-background!"
                        @click="viewMode = 'list'"
                    >
                        <List class="mr-1.5 h-3.5 w-3.5" />
                        List
                    </Button>
                </div>
            </div>

            <CalendarGrid
                v-if="viewMode === 'calendar'"
                :calendar-days="calendarDays"
                :days-of-week="daysOfWeek"
                :today="today"
                :current-year="currentYear"
                :current-month="currentMonth"
                :events-for-day="eventsForDay"
                :format-date-str="formatDateStr"
                :months="months"
                :open-create-dialog="openCreateDialog"
                :open-edit-dialog="openEditDialog"
            />

            <CalendarList
                v-else
                :events="futureEvents"
                :today="today"
                :months="months"
                :open-edit-dialog="openEditDialog"
            />
        </div>
    </div>

    <CalendarEventDialogs ref="dialogsRef" />
</template>
