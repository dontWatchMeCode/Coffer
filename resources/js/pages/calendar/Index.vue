<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    List,
    ListPlus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import {
    destroy as deleteCalendarEvent,
    store as storeCalendarEvent,
    update as updateCalendarEvent,
} from '@/routes/team/calendar/events';
import type { CalendarEventItem } from '@/types';

type Props = {
    events: CalendarEventItem[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

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

const createDialogOpen = ref(false);
const createFormKey = ref(0);
const createDate = ref('');

function openCreateDialog(day: number): void {
    createDate.value = formatDateStr(
        currentYear.value,
        currentMonth.value,
        day,
    );
    createDialogOpen.value = true;
}

function handleCreateClose(value: boolean): void {
    createDialogOpen.value = value;

    if (!value) {
        createFormKey.value++;
    }
}

const editEvent = ref<CalendarEventItem | null>(null);
const editFormKey = ref(0);
const editDialogOpen = computed({
    get: () => editEvent.value !== null,
    set: (value: boolean) => {
        if (!value) {
            editEvent.value = null;
            editFormKey.value++;
        }
    },
});

function openEditDialog(event: CalendarEventItem): void {
    editEvent.value = event;
    editFormKey.value++;
}

const deleteDialogOpen = ref(false);
const deletingEvent = ref<CalendarEventItem | null>(null);

function openDeleteDialog(event: CalendarEventItem): void {
    deletingEvent.value = event;
    editEvent.value = null;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!deletingEvent.value) {
        return;
    }

    const event = deletingEvent.value;
    deleteDialogOpen.value = false;
    deletingEvent.value = null;

    router.delete(
        deleteCalendarEvent({
            current_team: currentTeamSlug.value,
            event: event.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                editEvent.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Calendar" />

    <PageHeader title="Calendar" description="View and manage team events.">
        <template #actions>
            <Dialog :open="createDialogOpen" @update:open="handleCreateClose">
                <DialogTrigger as-child>
                    <Button
                        size="icon"
                        title="Create event"
                        class="cursor-pointer"
                    >
                        <ListPlus class="h-4 w-4" />
                    </Button>
                </DialogTrigger>

                <DialogContent>
                    <Form
                        :key="createFormKey"
                        v-bind="storeCalendarEvent.form(currentTeamSlug)"
                        reset-on-success
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                        @success="handleCreateClose(false)"
                    >
                        <DialogHeader>
                            <DialogTitle>Create event</DialogTitle>
                            <DialogDescription>
                                Add a new event to the calendar.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-event-title">Title</Label>
                            <Input
                                id="create-event-title"
                                name="title"
                                placeholder="Team standup"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-event-description"
                                >Description</Label
                            >
                            <textarea
                                id="create-event-description"
                                name="description"
                                :class="taskInputLikeClass"
                                rows="3"
                                placeholder="Optional description"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-event-date">Date</Label>
                            <Input
                                id="create-event-date"
                                name="date"
                                type="date"
                                :default-value="createDate"
                                required
                            />
                            <InputError :message="errors.date" />
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="processing">
                                Create event
                            </Button>
                        </div>
                    </Form>
                </DialogContent>
            </Dialog>
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

            <div
                v-if="viewMode === 'calendar'"
                class="grid grid-cols-7 gap-px overflow-hidden rounded-lg border bg-border"
            >
                <div
                    v-for="day in daysOfWeek"
                    :key="day"
                    class="bg-muted px-2 py-2 text-center text-xs font-medium text-muted-foreground"
                >
                    {{ day }}
                </div>

                <template v-for="(day, idx) in calendarDays" :key="idx">
                    <div
                        v-if="day === null"
                        class="min-h-[100px] bg-card p-1.5 dark:bg-card/50"
                    />
                    <div
                        v-else
                        class="group/day min-h-[100px] bg-card p-1.5 transition-colors hover:bg-accent/20 dark:bg-card/50"
                        :class="{
                            'bg-accent/30':
                                formatDateStr(
                                    currentYear,
                                    currentMonth,
                                    day,
                                ) === today,
                        }"
                        @dblclick="openCreateDialog(day)"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-full text-sm"
                                :class="{
                                    'bg-primary font-semibold text-primary-foreground':
                                        formatDateStr(
                                            currentYear,
                                            currentMonth,
                                            day,
                                        ) === today,
                                }"
                            >
                                {{ day }}
                            </span>
                        </div>

                        <div
                            v-if="eventsForDay(day).length > 0"
                            class="mt-1 space-y-0.5"
                        >
                            <button
                                v-for="event in eventsForDay(day).slice(0, 3)"
                                :key="event.id"
                                type="button"
                                class="flex w-full cursor-pointer items-center gap-1 truncate rounded-md bg-primary/10 px-1.5 py-0.5 text-left text-xs text-primary transition-colors hover:bg-primary/20"
                                @click="openEditDialog(event)"
                            >
                                <span class="truncate font-medium">
                                    {{ event.title }}
                                </span>
                            </button>
                            <span
                                v-if="eventsForDay(day).length > 3"
                                class="block px-1.5 text-[10px] text-muted-foreground"
                            >
                                +{{ eventsForDay(day).length - 3 }} more
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="event in monthEvents"
                    :key="event.id"
                    class="flex cursor-pointer items-center gap-4 rounded-lg border bg-card p-3 transition-colors hover:bg-accent/50 dark:bg-card/50"
                    @click="openEditDialog(event)"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-md text-xs"
                        :class="{
                            'bg-primary text-primary-foreground':
                                event.date === today,
                            'bg-muted text-muted-foreground':
                                event.date !== today,
                        }"
                    >
                        <span
                            class="text-[10px] leading-none font-medium uppercase"
                        >
                            {{ months[currentMonth].slice(0, 3) }}
                        </span>
                        <span class="text-sm leading-tight font-bold">{{
                            (event.date ?? '').split('-')[2]
                        }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ event.title }}</p>
                        <p
                            v-if="event.description"
                            class="truncate text-sm text-muted-foreground"
                        >
                            {{ event.description }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="monthEvents.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center"
                >
                    <CalendarDays
                        class="mb-2 h-8 w-8 text-muted-foreground/50"
                    />
                    <p class="text-sm text-muted-foreground">
                        No events this month.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete event</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-semibold text-foreground">{{
                        deletingEvent?.title
                    }}</span
                    >? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    variant="outline"
                    class="cursor-pointer"
                    @click="deleteDialogOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    class="cursor-pointer"
                    @click="confirmDelete"
                >
                    <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                    Delete
                </Button>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="editDialogOpen">
        <DialogContent v-if="editEvent">
            <Form
                :key="editFormKey"
                v-bind="
                    updateCalendarEvent.form({
                        current_team: currentTeamSlug,
                        event: editEvent.id,
                    })
                "
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="editEvent = null"
            >
                <DialogHeader>
                    <DialogTitle>Edit event</DialogTitle>
                    <DialogDescription>
                        Update the event details.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-event-title">Title</Label>
                    <Input
                        id="edit-event-title"
                        name="title"
                        :default-value="editEvent.title"
                        required
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-event-description">Description</Label>
                    <textarea
                        id="edit-event-description"
                        name="description"
                        :class="taskInputLikeClass"
                        rows="3"
                        :default-value="editEvent.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-event-date">Date</Label>
                    <Input
                        id="edit-event-date"
                        name="date"
                        type="date"
                        :default-value="editEvent.date ?? ''"
                        required
                    />
                    <InputError :message="errors.date" />
                </div>

                <div class="flex items-center justify-between">
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="cursor-pointer"
                        @click="openDeleteDialog(editEvent)"
                    >
                        <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                        Delete
                    </Button>

                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
