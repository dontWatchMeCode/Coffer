<script setup lang="ts">
import { CalendarDays } from 'lucide-vue-next';
import type { CalendarEventItem } from '@/types';

type Props = {
    monthEvents: CalendarEventItem[];
    today: string;
    currentMonth: number;
    months: string[];
    openEditDialog: (event: CalendarEventItem) => void;
};

defineProps<Props>();
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="event in monthEvents"
            :key="event.id"
            class="flex cursor-pointer items-center gap-4 rounded-lg border bg-card p-3 transition-colors hover:bg-accent/50 dark:bg-card/50"
            @click="openEditDialog(event)"
        >
            <div
                class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-md text-xs"
                :class="{
                    'bg-primary text-primary-foreground': event.date === today,
                    'bg-muted text-muted-foreground': event.date !== today,
                }"
            >
                <span class="text-[10px] leading-none font-medium uppercase">
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
            <CalendarDays class="mb-2 h-8 w-8 text-muted-foreground/50" />
            <p class="text-sm text-muted-foreground">No events this month.</p>
        </div>
    </div>
</template>
