<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    List,
    ListPlus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CalendarEventDialogs from '@/components/calendar/CalendarEventDialogs.vue';
import CalendarGrid from '@/components/calendar/CalendarGrid.vue';
import CalendarList from '@/components/calendar/CalendarList.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
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

const viewMode = ref<ViewMode>('calendar');

const currentMonthLabel = computed(
    () => `${months[currentMonth.value]} ${currentYear.value}`,
);

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

const monthEvents = computed(() => {
    const monthPrefix = formatDateStr(
        currentYear.value,
        currentMonth.value,
        1,
    ).slice(0, 7);

    return props.events
        .filter((e) => e.date?.startsWith(monthPrefix))
        .sort((a, b) => (a.date ?? '').localeCompare(b.date ?? ''));
});

function prevMonth(): void {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
}

function nextMonth(): void {
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
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        class="cursor-pointer"
                        @click="prevMonth"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="cursor-pointer"
                        @click="goToday"
                    >
                        Today
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        class="cursor-pointer"
                        @click="nextMonth"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                    <h2 class="ml-2 text-lg font-semibold">
                        {{ currentMonthLabel }}
                    </h2>
                </div>

                <div class="flex items-center rounded-lg border bg-muted p-0.5">
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="{
                            'bg-background shadow-sm': viewMode === 'calendar',
                        }"
                        class="cursor-pointer"
                        @click="viewMode = 'calendar'"
                    >
                        <CalendarDays class="mr-1.5 h-3.5 w-3.5" />
                        Calendar
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="{
                            'bg-background shadow-sm': viewMode === 'list',
                        }"
                        class="cursor-pointer"
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
                :month-events="monthEvents"
                :today="today"
                :current-month="currentMonth"
                :months="months"
                :open-edit-dialog="openEditDialog"
            />
        </div>
    </div>

    <CalendarEventDialogs ref="dialogsRef" />
</template>
