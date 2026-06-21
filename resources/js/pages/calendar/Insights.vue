<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { VisAxis, VisGroupedBar, VisXYContainer } from '@unovis/vue';
import { CalendarDays, CalendarRange, Clock3 } from 'lucide-vue-next';
import { computed } from 'vue';
import DashboardCard from '@/components/dashboard/DashboardCard.vue';
import KpiCard from '@/components/dashboard/KpiCard.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from '@/components/ui/chart';
import { formatChartMonth } from '@/lib/chart';
import {
    insights as calendarInsightsRoute,
    index as calendarIndex,
} from '@/routes/team/calendar';
import type { Team } from '@/types';

type MonthPoint = { month: string; count: number };
type UpcomingEvent = {
    id: number;
    title: string;
    date: string | null;
    time: string | null;
    url: string;
};

type InsightsData = {
    kpis: {
        thisMonth: number;
        next7Days: number;
        next30Days: number;
    };
    eventsPerMonth: MonthPoint[];
    upcoming: UpcomingEvent[];
};

type RangeOption = { value: string; label: string };

const props = defineProps<{
    insights: InsightsData;
    range: string;
    rangeOptions: RangeOption[];
    currentTeam?: Team | null;
}>();

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
});

const chartConfig = {
    count: {
        label: 'Events',
        color: 'var(--chart-3)',
    },
} as const;

const chartData = computed(() =>
    props.insights.eventsPerMonth.map(
        (point): { date: Date; count: number } => ({
            date: new Date(`${point.month}-01T00:00:00`),
            count: point.count,
        }),
    ),
);

type ChartPoint = (typeof chartData.value)[number];

const chartX = (d: ChartPoint): Date => d.date;
const chartY = [(d: ChartPoint): number => d.count];

function formatDate(value: string | null): string {
    if (!value) {
        return 'No date';
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return '';
    }

    const date = new Date(year, month - 1, day);

    return Number.isNaN(date.getTime()) ? '' : dateFormatter.format(date);
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Calendar',
                href: calendarIndex(pageProps.currentTeam?.slug).url,
            },
            {
                title: 'Insights',
                href: calendarInsightsRoute(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Calendar · Insights" />

    <PageHeader
        title="Calendar Insights"
        :subtitle="currentTeam ? `· ${currentTeam.name}` : undefined"
        description="Event volume, monthly cadence, and upcoming schedule."
        :back-href="calendarIndex(currentTeam?.slug).url"
        back-label="Back to calendar"
    >
        <template #actions>
            <Button
                variant="outline"
                size="sm"
                title="Main view"
                as-child
                class="cursor-pointer"
            >
                <Link :href="calendarIndex(currentTeam?.slug).url">
                    Main view
                </Link>
            </Button>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-4 md:grid-cols-3">
                <KpiCard
                    label="Events this month"
                    :value="insights.kpis.thisMonth"
                    detail="current calendar month"
                    :icon="CalendarRange"
                />
                <KpiCard
                    label="Next 7 days"
                    :value="insights.kpis.next7Days"
                    detail="upcoming this week"
                    :icon="Clock3"
                />
                <KpiCard
                    label="Next 30 days"
                    :value="insights.kpis.next30Days"
                    detail="upcoming this month"
                    :icon="CalendarDays"
                />
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-12">
                <DashboardCard
                    title="Events per month"
                    description="Volume of scheduled events over time."
                    :range="range"
                    :range-options="rangeOptions"
                    class="self-start xl:col-span-7"
                >
                    <ChartContainer
                        :config="chartConfig"
                        class="h-[280px] w-full"
                        :cursor="true"
                    >
                        <VisXYContainer :data="chartData">
                            <VisGroupedBar
                                :x="chartX"
                                :y="chartY"
                                :color="chartConfig.count.color"
                                :rounded-corners="4"
                                bar-padding="0.25"
                                group-padding="0"
                            />
                            <VisAxis
                                type="x"
                                :x="chartX"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="false"
                                :tick-format="formatChartMonth"
                                :tick-values="chartData.map((d) => d.date)"
                            />
                            <VisAxis
                                type="y"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="true"
                                :tick-format="
                                    (d: number) =>
                                        Number.isInteger(d) ? d.toString() : ''
                                "
                            />
                            <ChartCrosshair
                                :x="chartX"
                                :y="chartY"
                                :template="
                                    componentToString(
                                        chartConfig,
                                        ChartTooltipContent,
                                        {
                                            labelFormatter: (
                                                d: number | Date | string,
                                            ) => formatChartMonth(d),
                                        },
                                    )
                                "
                                :color="chartConfig.count.color"
                            />
                            <ChartTooltip />
                        </VisXYContainer>
                    </ChartContainer>
                </DashboardCard>

                <DashboardCard
                    title="Upcoming events"
                    :description="
                        insights.upcoming.length
                            ? `${insights.upcoming.length} next up`
                            : 'Nothing scheduled'
                    "
                    class="self-start xl:col-span-5"
                >
                    <ul
                        v-if="insights.upcoming.length"
                        class="-my-1 divide-y divide-border/70"
                    >
                        <li v-for="event in insights.upcoming" :key="event.id">
                            <Link
                                :href="event.url"
                                class="group flex items-center justify-between gap-3 py-2"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium group-hover:text-foreground"
                                    >
                                        {{ event.title }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(event.date) }}
                                        <span v-if="event.time">
                                            · {{ event.time }}</span
                                        >
                                    </p>
                                </div>
                                <Badge
                                    variant="secondary"
                                    class="shrink-0 tabular-nums"
                                >
                                    {{ event.time ?? 'All day' }}
                                </Badge>
                            </Link>
                        </li>
                    </ul>
                    <div
                        v-else
                        class="rounded-2xl border border-dashed bg-background/70 p-6 text-sm text-muted-foreground"
                    >
                        No upcoming events. Add one from the calendar page.
                    </div>
                </DashboardCard>
            </div>
        </div>
    </div>
</template>
