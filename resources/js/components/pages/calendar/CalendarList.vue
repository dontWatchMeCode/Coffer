<script setup lang="ts">
import { CalendarDays } from 'lucide-vue-next';
import { computed } from 'vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import type { CalendarEventItem } from '@/types';

type Props = {
    events: CalendarEventItem[];
    today: string;
    months: string[];
    openEditDialog: (event: CalendarEventItem) => void;
};

const props = defineProps<Props>();

type EventDisplay = {
    event: CalendarEventItem;
    monthShort: string;
    day: string;
    isToday: boolean;
};

type GroupedEvents = {
    monthKey: string;
    monthLabel: string;
    events: EventDisplay[];
};

function parseDateParts(
    dateStr: string | null | undefined,
): { monthIndex: number; day: string } | null {
    if (!dateStr) {
        return null;
    }

    const dateOnly = dateStr.split('T')[0];
    const parts = dateOnly.split('-');

    if (parts.length < 3) {
        return null;
    }

    const monthIndex = Number.parseInt(parts[1], 10) - 1;

    if (Number.isNaN(monthIndex) || monthIndex < 0 || monthIndex > 11) {
        return null;
    }

    return { monthIndex, day: parts[2] };
}

const groupedEvents = computed<GroupedEvents[]>(() => {
    const groups = new Map<string, EventDisplay[]>();
    const todayDateOnly = props.today.split('T')[0];

    for (const event of props.events) {
        const date = event.date ?? '';
        const monthKey = date.slice(0, 7);

        if (!monthKey) {
            continue;
        }

        const parsed = parseDateParts(date);

        if (!parsed) {
            continue;
        }

        if (!groups.has(monthKey)) {
            groups.set(monthKey, []);
        }

        groups.get(monthKey)!.push({
            event,
            monthShort: props.months[parsed.monthIndex]?.slice(0, 3) ?? '',
            day: parsed.day,
            isToday: date.split('T')[0] === todayDateOnly,
        });
    }

    return Array.from(groups.entries())
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([monthKey, events]) => {
            const parsed = parseDateParts(monthKey + '-01');
            const monthLabel = parsed
                ? `${props.months[parsed.monthIndex]} ${monthKey.split('-')[0]}`
                : monthKey;

            return {
                monthKey,
                monthLabel,
                events: events.sort((a, b) =>
                    (a.event.date ?? '').localeCompare(b.event.date ?? ''),
                ),
            };
        });
});
</script>

<template>
    <div v-if="groupedEvents.length > 0" class="space-y-6">
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
                <ListItem
                    v-for="item in group.events"
                    :key="item.event.id"
                    @click="openEditDialog(item.event)"
                >
                    <div class="flex items-center gap-4">
                        <ListItemIcon
                            :class="{
                                'bg-primary text-primary-foreground':
                                    item.isToday,
                                'bg-muted text-muted-foreground': !item.isToday,
                            }"
                        >
                            <div class="flex flex-col items-center">
                                <span
                                    class="text-[10px] leading-none font-medium uppercase"
                                >
                                    {{ item.monthShort }}
                                </span>
                                <span class="text-sm leading-tight font-bold">{{
                                    item.day
                                }}</span>
                            </div>
                        </ListItemIcon>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline gap-2">
                                <p class="min-w-0 flex-1 truncate font-medium">
                                    {{ item.event.title }}
                                </p>
                                <span
                                    v-if="item.event.time"
                                    class="shrink-0 text-xs text-muted-foreground"
                                >
                                    {{ item.event.time }}
                                </span>
                            </div>
                            <p
                                v-if="item.event.description"
                                class="truncate text-sm text-muted-foreground"
                            >
                                {{ item.event.description }}
                            </p>
                        </div>
                    </div>
                </ListItem>
            </div>
        </div>
    </div>

    <EmptyState v-else title="No upcoming events.">
        <template #icon>
            <CalendarDays class="h-8 w-8" />
        </template>
    </EmptyState>
</template>
