<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CurveType } from '@unovis/ts';
import { VisArea, VisAxis, VisXYContainer } from '@unovis/vue';
import { CalendarClock, CircleDollarSign, TrendingUp } from 'lucide-vue-next';
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
    insights as subscriptionsInsightsRoute,
    index as subscriptionsIndex,
} from '@/routes/team/subscriptions';
import type { Team } from '@/types';

type SpendPoint = { month: string; spend: number };
type CategoryPoint = { category: string; spend: number };

type InsightsData = {
    kpis: {
        monthlySpend: string;
        activeCount: number;
        upcomingRenewals: number;
    };
    spendTrend: SpendPoint[];
    categoryBreakdown: CategoryPoint[];
};

type RangeOption = { value: string; label: string };

const props = defineProps<{
    insights: InsightsData;
    range: string;
    rangeOptions: RangeOption[];
    currentTeam?: Team | null;
}>();

const currencyFormatter = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
});

const monthlySpend = computed(() =>
    Number.parseFloat(props.insights.kpis.monthlySpend),
);

const areaData = computed(() => {
    const points = props.insights.spendTrend.map(
        (point): { date: Date; spend: number } => ({
            date: new Date(`${point.month}-01T00:00:00`),
            spend: point.spend,
        }),
    );

    if (points.length !== 1) {
        return points;
    }

    const monthEnd = new Date(points[0].date);
    monthEnd.setMonth(monthEnd.getMonth() + 1, 0);

    return [points[0], { date: monthEnd, spend: points[0].spend }];
});

const areaTickValues = computed(() =>
    props.insights.spendTrend.map(
        (point): Date => new Date(`${point.month}-01T00:00:00`),
    ),
);

const areaConfig = {
    spend: {
        label: 'Monthly spend',
        color: 'var(--chart-1)',
    },
} as const;

type AreaPoint = (typeof areaData.value)[number];

const areaX = (d: AreaPoint): Date => d.date;
const areaY = (d: AreaPoint): number => d.spend;

const totalCategories = computed(() => props.insights.categoryBreakdown.length);
const topCategories = computed(() =>
    props.insights.categoryBreakdown.slice(0, 5),
);
const totalCategorySpend = computed(() =>
    props.insights.categoryBreakdown.reduce(
        (sum, point) => sum + point.spend,
        0,
    ),
);

function formatSpend(value: number): string {
    return currencyFormatter.format(value);
}

function categoryShare(spend: number): string {
    if (totalCategorySpend.value <= 0) {
        return '0%';
    }

    return `${Math.round((spend / totalCategorySpend.value) * 100)}%`;
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Subscriptions',
                href: subscriptionsIndex(pageProps.currentTeam?.slug).url,
            },
            {
                title: 'Insights',
                href: subscriptionsInsightsRoute(pageProps.currentTeam?.slug)
                    .url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Subscriptions · Insights" />

    <PageHeader
        title="Subscriptions Insights"
        :subtitle="currentTeam ? `· ${currentTeam.name}` : undefined"
        description="Spending trends, category breakdown, and renewal activity."
        :back-href="subscriptionsIndex(currentTeam?.slug).url"
        back-label="Back to subscriptions"
    >
        <template #actions>
            <Button
                variant="outline"
                size="sm"
                title="Main view"
                as-child
                class="cursor-pointer"
            >
                <Link :href="subscriptionsIndex(currentTeam?.slug).url">
                    Main view
                </Link>
            </Button>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-4 md:grid-cols-3">
                <KpiCard
                    label="Monthly spend"
                    :value="formatSpend(monthlySpend)"
                    :detail="`${insights.kpis.activeCount} active`"
                    :icon="CircleDollarSign"
                />
                <KpiCard
                    label="Active subscriptions"
                    :value="insights.kpis.activeCount"
                    detail="recurring services"
                    :icon="TrendingUp"
                />
                <KpiCard
                    label="Upcoming renewals"
                    :value="insights.kpis.upcomingRenewals"
                    detail="next 30 days"
                    :icon="CalendarClock"
                />
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-12">
                <DashboardCard
                    title="Monthly spend"
                    description="Normalized active subscription spend across the selected range."
                    :range="range"
                    :range-options="rangeOptions"
                    class="self-start xl:col-span-7"
                >
                    <ChartContainer
                        :config="areaConfig"
                        class="h-[280px] w-full"
                        :cursor="true"
                    >
                        <VisXYContainer :data="areaData">
                            <VisArea
                                :x="areaX"
                                :y="areaY"
                                :color="areaConfig.spend.color"
                                :curve-type="CurveType.MonotoneX"
                                :show-gradient="true"
                            />
                            <VisAxis
                                type="x"
                                :x="areaX"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="false"
                                :tick-format="formatChartMonth"
                                :tick-values="areaTickValues"
                            />
                            <VisAxis
                                type="y"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="true"
                                :tick-format="(d: number) => formatSpend(d)"
                            />
                            <ChartCrosshair
                                :x="areaX"
                                :y="areaY"
                                :template="
                                    componentToString(
                                        areaConfig,
                                        ChartTooltipContent,
                                        {
                                            labelFormatter: (
                                                d: number | Date | string,
                                            ) => formatChartMonth(d),
                                            valueFormatter: (value: unknown) =>
                                                formatSpend(Number(value)),
                                        },
                                    )
                                "
                                :color="areaConfig.spend.color"
                            />
                            <ChartTooltip />
                        </VisXYContainer>
                    </ChartContainer>
                </DashboardCard>

                <DashboardCard
                    title="Category breakdown"
                    :description="
                        totalCategories
                            ? `${totalCategories} categories`
                            : 'No categorized subscriptions'
                    "
                    class="self-start xl:col-span-5"
                >
                    <ul
                        v-if="topCategories.length"
                        class="-my-1 divide-y divide-border/70"
                    >
                        <li
                            v-for="point in topCategories"
                            :key="point.category"
                            class="flex items-center justify-between gap-3 py-2"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ point.category }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ categoryShare(point.spend) }} of spend
                                </p>
                            </div>
                            <Badge variant="secondary" class="tabular-nums">
                                {{ formatSpend(point.spend) }}
                            </Badge>
                        </li>
                    </ul>
                    <div
                        v-else
                        class="rounded-2xl border border-dashed bg-background/70 p-6 text-sm text-muted-foreground"
                    >
                        No active subscriptions yet. Add one to see category
                        breakdown.
                    </div>
                    <div
                        v-if="totalCategories > topCategories.length"
                        class="mt-3 text-xs text-muted-foreground"
                    >
                        Showing top {{ topCategories.length }} of
                        {{ totalCategories }} categories.
                    </div>
                </DashboardCard>
            </div>
        </div>
    </div>
</template>
