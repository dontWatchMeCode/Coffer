<script setup lang="ts">
import type { CalendarEventItem } from '@/types';

type Props = {
    calendarDays: (number | null)[];
    daysOfWeek: string[];
    today: string;
    currentYear: number;
    currentMonth: number;
    eventsForDay: (day: number | null) => CalendarEventItem[];
    formatDateStr: (year: number, month: number, day: number) => string;
    months: string[];
    openCreateDialog: (day: number) => void;
    openEditDialog: (event: CalendarEventItem) => void;
};

defineProps<Props>();
</script>

<template>
    <div
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
                        formatDateStr(currentYear, currentMonth, day) === today,
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
</template>
