<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, PieChart, Trash2 } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CalendarEventDialogs from '@/components/pages/calendar/CalendarEventDialogs.vue';
import CalendarGrid from '@/components/pages/calendar/CalendarGrid.vue';
import CalendarList from '@/components/pages/calendar/CalendarList.vue';
import CalendarMonthNav from '@/components/pages/calendar/CalendarMonthNav.vue';
import CalendarViewToggle from '@/components/pages/calendar/CalendarViewToggle.vue';
import EventDetailOverlay from '@/components/pages/calendar/EventDetailOverlay.vue';
import { Button } from '@/components/ui/button';
import { useCalendarViewMode } from '@/composables/useCalendarViewMode';
import { useListDetailOverlay } from '@/composables/useListDetailOverlay';
import {
    index as calendarIndex,
    trash as calendarTrash,
    insights as calendarInsights,
} from '@/routes/team/calendar';
import { edit as editEventRoute } from '@/routes/team/calendar/events';
import type {
    ActivityHistoryConfig,
    CalendarEventItem,
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
    calendarEvents: CalendarEventItem[];
    events: PaginatedData<CalendarEventItem>;
    searchMatchIds: number[] | null;
    event?: CalendarEventItem;
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

type EventPageProps = PageProps & Partial<Props>;

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const searchQuery = ref<string>(
    new URLSearchParams(window.location.search).get('search') ?? '',
);

const now = new Date();
const urlParams = new URLSearchParams(window.location.search);
const currentYear = ref(
    urlParams.has('year') ? Number(urlParams.get('year')) : now.getFullYear(),
);
const currentMonth = ref(
    urlParams.has('month')
        ? Number(urlParams.get('month')) - 1
        : now.getMonth(),
);

let calendarTimeout: ReturnType<typeof setTimeout> | null = null;

const hasListData = computed(() =>
    Boolean(props.events || props.calendarEvents),
);

const {
    closeDetail,
    rememberSavedItem,
    getPendingSavedItem,
    clearPendingSavedItem,
} = useListDetailOverlay('calendar', currentTeamSlug.value, hasListData.value);

function reloadCalendar(): void {
    if (calendarTimeout) {
        clearTimeout(calendarTimeout);
    }

    router.visit(calendarIndex(currentTeamSlug.value).url, {
        data: {
            search: searchQuery.value || undefined,
            month: currentMonth.value + 1,
            year: currentYear.value,
        },
        only: ['calendarEvents', 'events', 'searchMatchIds'],
        reset: ['events'],
        preserveScroll: true,
        preserveState: true,
    });
}

watch(searchQuery, () => {
    if (calendarTimeout) {
        clearTimeout(calendarTimeout);
    }

    calendarTimeout = setTimeout(reloadCalendar, 300);
});

watch(
    () => [currentMonth.value, currentYear.value],
    () => {
        if (calendarTimeout) {
            clearTimeout(calendarTimeout);
            calendarTimeout = null;
        }

        reloadCalendar();
    },
);

onUnmounted(() => {
    if (calendarTimeout) {
        clearTimeout(calendarTimeout);
    }
});

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: {
        currentTeam?: Team | null;
        event?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Calendar',
                href: calendarIndex(pageProps.currentTeam?.slug).url,
            },
            ...(pageProps.event ? [{ title: pageProps.event.title }] : []),
        ],
    }),
});

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

const { viewMode } = useCalendarViewMode();

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

const dayEventsMap = computed(() => {
    const map = new Map<
        number,
        { event: CalendarEventItem; matchesSearch: boolean }[]
    >();
    const yearMonth = `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}`;

    for (const event of props.calendarEvents) {
        if (!event.date || !event.date.startsWith(yearMonth)) {
            continue;
        }

        const day = Number.parseInt(event.date.split('-')[2], 10);

        if (!map.has(day)) {
            map.set(day, []);
        }

        map.get(day)!.push({
            event,
            matchesSearch:
                props.searchMatchIds === null ||
                props.searchMatchIds.includes(event.id),
        });
    }

    return map;
});

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
        {
            only: ['event', 'recordLinks', 'recordTags', 'activityHistory'],
            preserveScroll: true,
        },
    );
}

function replaceLoadedEvent(updatedEvent: CalendarEventItem): boolean {
    const replacedInCalendar = props.calendarEvents?.some(
        (e) => e.id === updatedEvent.id,
    );
    const replacedInPaginated = props.events?.data.some(
        (e) => e.id === updatedEvent.id,
    );

    if (!replacedInCalendar && !replacedInPaginated) {
        return false;
    }

    const patch = (arr: CalendarEventItem[]) =>
        arr.map((e) => (e.id === updatedEvent.id ? updatedEvent : e));

    if (replacedInCalendar) {
        router.replaceProp<EventPageProps>(
            'calendarEvents',
            (events: unknown) => {
                if (!Array.isArray(events)) {
                    return events;
                }

                return patch(events as CalendarEventItem[]);
            },
        );
    }

    if (replacedInPaginated) {
        router.replaceProp<EventPageProps>('events.data', (events: unknown) => {
            if (!Array.isArray(events)) {
                return events;
            }

            return patch(events as CalendarEventItem[]);
        });
    }

    return true;
}

function applyPendingSavedEvent(): void {
    if (props.event) {
        return;
    }

    const event = getPendingSavedItem<CalendarEventItem & { id: number }>();

    if (!event || typeof event.id !== 'number') {
        clearPendingSavedItem();

        return;
    }

    replaceLoadedEvent(event);
    clearPendingSavedItem();
}

function closeEvent(): void {
    closeDetail(calendarIndex(currentTeamSlug.value).url);
}

function onSaved(event: CalendarEventItem): void {
    rememberSavedItem(event);
    replaceLoadedEvent(event);
}

watch(
    () => [props.event?.id, props.calendarEvents, props.events?.data],
    () => applyPendingSavedEvent(),
    { immediate: true, flush: 'post' },
);
</script>

<template>
    <Head :title="props.event ? props.event.title : 'Calendar'" />

    <div v-if="!props.event">
        <PageHeader title="Calendar" description="View and manage team events.">
            <template #actions>
                <Button
                    variant="outline"
                    size="sm"
                    title="Insights"
                    as-child
                    class="cursor-pointer gap-1.5"
                >
                    <Link :href="calendarInsights(currentTeamSlug).url">
                        <PieChart class="h-3.5 w-3.5" />
                        Insights
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="min-w-0 flex-1 px-4 py-6">
            <div class="mx-auto w-full max-w-7xl">
                <div class="mb-4 flex items-center justify-end gap-3">
                    <SearchInput
                        v-model="searchQuery"
                        data-testid="calendar-search-input"
                        placeholder="Search events..."
                    />
                </div>

                <div class="min-w-0 space-y-4">
                    <div class="flex items-center justify-between">
                        <CalendarMonthNav
                            v-if="viewMode === 'calendar'"
                            :current-month="currentMonth"
                            :current-year="currentYear"
                            :months="months"
                            @update:current-month="currentMonth = $event"
                            @update:current-year="currentYear = $event"
                        />

                        <div class="ml-auto flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="icon"
                                title="Trash"
                                as-child
                            >
                                <Link
                                    :href="calendarTrash(currentTeamSlug).url"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Link>
                            </Button>

                            <Button
                                size="icon"
                                title="Create event"
                                class="cursor-pointer"
                                @click="dialogsRef?.openCreateDialogNoDate()"
                            >
                                <ListPlus class="h-4 w-4" />
                            </Button>

                            <CalendarViewToggle />
                        </div>
                    </div>

                    <CalendarGrid
                        v-if="viewMode === 'calendar'"
                        :calendar-days="calendarDays"
                        :days-of-week="daysOfWeek"
                        :today="today"
                        :current-year="currentYear"
                        :current-month="currentMonth"
                        :day-events-map="dayEventsMap"
                        :format-date-str="formatDateStr"
                        :months="months"
                        :open-create-dialog="openCreateDialog"
                        :open-edit-dialog="openEditDialog"
                    />

                    <InfiniteScroll
                        v-if="viewMode === 'list'"
                        data="events"
                        :buffer="1200"
                    >
                        <CalendarList
                            :events="props.events.data"
                            :today="today"
                            :months="months"
                            :open-edit-dialog="openEditDialog"
                        />
                    </InfiniteScroll>
                </div>
            </div>
        </div>

        <CalendarEventDialogs ref="dialogsRef" />
    </div>

    <EventDetailOverlay
        v-if="props.event"
        :event="props.event"
        :record-links="props.recordLinks"
        :record-tags="props.recordTags"
        :activity-history="props.activityHistory"
        @close="closeEvent"
        @saved="onSaved"
    />
</template>
