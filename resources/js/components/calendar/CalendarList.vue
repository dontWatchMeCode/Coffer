<script setup lang="ts">
import { CalendarDays } from 'lucide-vue-next';
import { computed } from 'vue';
import type { CalendarEventItem } from '@/types';

type Props = {
    events: CalendarEventItem[];
    today: string;
    months: string[];
    openEditDialog: (event: CalendarEventItem) => void;
};

const props = defineProps<Props>();

type GroupedEvents = {
    monthKey: string;
    monthLabel: string;
    events: CalendarEventItem[];
};

const groupedEvents = computed<GroupedEvents[]>(() => {
    const groups = new Map<string, CalendarEventItem[]>();

    for (const event of props.events) {
        const date = event.date ?? '';
        const monthKey = date.slice(0, 7);

        if (!monthKey) {
            continue;
        }

        if (!groups.has(monthKey)) {
            groups.set(monthKey, []);
        }

        groups.get(monthKey)!.push(event);
    }

    return Array.from(groups.entries())
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([monthKey, events]) => {
            const monthIndex = Number.parseInt(monthKey.split('-')[1], 10) - 1;
            const year = monthKey.split('-')[0];

            return {
                monthKey,
                monthLabel: `${props.months[monthIndex]} ${year}`,
                events,
            };
        });
});
</script>

<template>
    <div class="space-y-6">
        <div v-for="group in groupedEvents" :key="group.monthKey">
            <h3
                class="mb-2 text-sm font-medium text-muted-foreground"
                :class="{
                    'text-foreground': group.monthKey === today.slice(0, 7),
                }"
            >
                {{ group.monthLabel }}
            </h3>

            <div class="space-y-2">
                <div
                    v-for="event in group.events"
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
                            {{
                                months[
                                    Number.parseInt(
                                        (event.date ?? '').split('-')[1],
                                        10,
                                    ) - 1
                                ].slice(0, 3)
                            }}
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
            </div>
        </div>

        <div
            v-if="groupedEvents.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center"
        >
            <CalendarDays class="mb-2 h-8 w-8 text-muted-foreground/50" />
            <p class="text-sm text-muted-foreground">No upcoming events.</p>
        </div>
    </div>
</template>
