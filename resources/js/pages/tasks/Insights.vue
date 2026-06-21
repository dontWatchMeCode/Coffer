<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { VisAxis, VisGroupedBar, VisXYContainer } from '@unovis/vue';
import { CheckCircle2, Clock4, ListTodo } from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed } from 'vue';
import DashboardCard from '@/components/dashboard/DashboardCard.vue';
import KpiCard from '@/components/dashboard/KpiCard.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from '@/components/ui/chart';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatChartMonth } from '@/lib/chart';
import {
    insights as tasksInsightsRoute,
    index as tasksIndex,
} from '@/routes/team/tasks';
import type { Team } from '@/types';

type StatusRow = { status: string; label: string; count: number };
type AssigneePoint = { assignee: string; count: number };
type TrendRow = { month: string; created: number };

type InsightsData = {
    kpis: {
        completionRate: number;
        overdue: number;
        totalOpen: number;
    };
    statusDistribution: StatusRow[];
    assignmentDistribution: AssigneePoint[];
    createdTrend: TrendRow[];
};

type RangeOption = { value: string; label: string };
type ProjectOption = { id: number; name: string };

const props = defineProps<{
    insights: InsightsData;
    range: string;
    rangeOptions: RangeOption[];
    selectedProjectId: number | null;
    projectOptions: ProjectOption[];
    currentTeam?: Team | null;
}>();

const statusPalette = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

const statusChartConfig = computed<ChartConfig>(() => {
    const config: ChartConfig = {
        count: {
            label: 'Tasks',
            color: undefined,
        },
    };

    props.insights.statusDistribution.forEach((point, index) => {
        config[point.label] = {
            label: point.label,
            color: statusPalette[index % statusPalette.length],
        };
    });

    return config;
});

const statusData = computed(() =>
    props.insights.statusDistribution.map((point, index) => ({
        index,
        label: point.label,
        count: point.count,
        fill: statusChartConfig.value[point.label]?.color ?? 'var(--chart-1)',
    })),
);

type StatusChartPoint = (typeof statusData.value)[number];

const totalStatusCount = computed(() =>
    statusData.value.reduce((sum, point) => sum + point.count, 0),
);

const statusX = (d: StatusChartPoint): number => d.index;
const statusY = [(d: StatusChartPoint): number => d.count];

const trendData = computed(() =>
    props.insights.createdTrend.map(
        (point): { date: Date; created: number } => ({
            date: new Date(`${point.month}-01T00:00:00`),
            created: point.created,
        }),
    ),
);

const trendConfig = {
    created: {
        label: 'Tasks created',
        color: 'var(--chart-2)',
    },
} as const;

type TrendChartPoint = (typeof trendData.value)[number];

const trendX = (d: TrendChartPoint): Date => d.date;
const trendY = [(d: TrendChartPoint): number => d.created];

const topAssignees = computed(() =>
    props.insights.assignmentDistribution.slice(0, 6),
);

const totalAssigned = computed(() =>
    props.insights.assignmentDistribution.reduce(
        (sum, point) => sum + point.count,
        0,
    ),
);

const selectedProjectValue = computed(() =>
    props.selectedProjectId === null ? 'all' : String(props.selectedProjectId),
);

function formatStatus(value: number | Date | string): string {
    const index = Number(value);

    return statusData.value[index]?.label ?? '';
}

function onProjectChange(value: AcceptableValue): void {
    if (typeof value !== 'string') {
        return;
    }

    const url = new URL(window.location.href);

    if (value === 'all') {
        url.searchParams.delete('project');
    } else {
        url.searchParams.set('project', value);
    }

    router.visit(url.pathname + url.search + url.hash, {
        preserveScroll: true,
        preserveState: true,
        only: ['insights', 'selectedProjectId', 'projectOptions'],
    });
}

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Tasks',
                href: tasksIndex(pageProps.currentTeam?.slug).url,
            },
            {
                title: 'Insights',
                href: tasksInsightsRoute(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Tasks · Insights" />

    <PageHeader
        title="Tasks Insights"
        :subtitle="currentTeam ? `· ${currentTeam.name}` : undefined"
        description="Completion, distribution, and recent activity."
        :back-href="tasksIndex(currentTeam?.slug).url"
        back-label="Back to tasks"
    >
        <template #actions>
            <Button
                variant="outline"
                size="sm"
                title="Main view"
                as-child
                class="cursor-pointer"
            >
                <Link :href="tasksIndex(currentTeam?.slug).url">
                    Main view
                </Link>
            </Button>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end"
            >
                <span class="text-sm font-medium text-muted-foreground">
                    Project
                </span>
                <Select
                    :model-value="selectedProjectValue"
                    @update:model-value="onProjectChange"
                >
                    <SelectTrigger size="sm" class="w-full sm:w-[220px]">
                        <SelectValue placeholder="Select project" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All projects</SelectItem>
                        <SelectItem
                            v-for="project in projectOptions"
                            :key="project.id"
                            :value="String(project.id)"
                        >
                            {{ project.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <KpiCard
                    label="Completion rate"
                    :value="`${insights.kpis.completionRate}%`"
                    detail="of all tasks"
                    :icon="CheckCircle2"
                />
                <KpiCard
                    label="Overdue"
                    :value="insights.kpis.overdue"
                    detail="open and past due"
                    :icon="Clock4"
                />
                <KpiCard
                    label="Open tasks"
                    :value="insights.kpis.totalOpen"
                    detail="currently in flight"
                    :icon="ListTodo"
                />
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-12">
                <DashboardCard
                    title="Tasks created"
                    description="Volume of new tasks per month."
                    :range="range"
                    :range-options="rangeOptions"
                    class="self-start xl:col-span-7"
                >
                    <ChartContainer
                        :config="trendConfig"
                        class="h-[280px] w-full"
                        :cursor="true"
                    >
                        <VisXYContainer :data="trendData">
                            <VisGroupedBar
                                :x="trendX"
                                :y="trendY"
                                :color="trendConfig.created.color"
                                :rounded-corners="4"
                                bar-padding="0.25"
                                group-padding="0"
                            />
                            <VisAxis
                                type="x"
                                :x="trendX"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="false"
                                :tick-format="formatChartMonth"
                                :tick-values="trendData.map((d) => d.date)"
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
                                :x="trendX"
                                :y="trendY"
                                :template="
                                    componentToString(
                                        trendConfig,
                                        ChartTooltipContent,
                                        {
                                            labelFormatter: (
                                                d: number | Date | string,
                                            ) => formatChartMonth(d),
                                        },
                                    )
                                "
                                :color="trendConfig.created.color"
                            />
                            <ChartTooltip />
                        </VisXYContainer>
                    </ChartContainer>
                </DashboardCard>

                <DashboardCard
                    title="Assignment distribution"
                    :description="
                        totalAssigned
                            ? `${totalAssigned} tasks in range`
                            : 'No tasks in range'
                    "
                    :range="range"
                    :range-options="rangeOptions"
                    class="self-start xl:col-span-5"
                >
                    <ul
                        v-if="topAssignees.length"
                        class="-my-1 divide-y divide-border/70"
                    >
                        <li
                            v-for="point in topAssignees"
                            :key="point.assignee"
                            class="flex items-center justify-between gap-3 py-2"
                        >
                            <span class="truncate text-sm font-medium">
                                {{ point.assignee }}
                            </span>
                            <Badge variant="secondary" class="tabular-nums">
                                {{ point.count }}
                            </Badge>
                        </li>
                    </ul>
                    <div
                        v-else
                        class="rounded-2xl border border-dashed bg-background/70 p-6 text-sm text-muted-foreground"
                    >
                        No tasks have been created in this range yet.
                    </div>
                </DashboardCard>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-12">
                <DashboardCard
                    title="Status breakdown"
                    :description="
                        totalStatusCount
                            ? `${totalStatusCount} tasks in range`
                            : 'No tasks in range'
                    "
                    :range="range"
                    :range-options="rangeOptions"
                    class="self-start xl:col-span-12"
                >
                    <div v-if="totalStatusCount > 0">
                        <ChartContainer
                            :config="statusChartConfig"
                            class="h-[280px] w-full"
                            :cursor="true"
                        >
                            <VisXYContainer :data="statusData">
                                <VisGroupedBar
                                    :x="statusX"
                                    :y="statusY"
                                    :color="(d: StatusChartPoint) => d.fill"
                                    :rounded-corners="4"
                                    bar-padding="0.25"
                                    group-padding="0"
                                />
                                <VisAxis
                                    type="x"
                                    :x="statusX"
                                    :tick-line="false"
                                    :domain-line="false"
                                    :grid-line="false"
                                    :tick-format="formatStatus"
                                    :tick-values="
                                        statusData.map((d) => d.index)
                                    "
                                />
                                <VisAxis
                                    type="y"
                                    :tick-line="false"
                                    :domain-line="false"
                                    :grid-line="true"
                                    :tick-format="
                                        (d: number) =>
                                            Number.isInteger(d)
                                                ? d.toString()
                                                : ''
                                    "
                                />
                                <ChartCrosshair
                                    :x="statusX"
                                    :y="statusY"
                                    :template="
                                        componentToString(
                                            statusChartConfig,
                                            ChartTooltipContent,
                                            {
                                                labelFormatter: (
                                                    d: number | Date | string,
                                                ) => formatStatus(d),
                                            },
                                        )
                                    "
                                />
                                <ChartTooltip />
                            </VisXYContainer>
                        </ChartContainer>
                    </div>
                    <div
                        v-else
                        class="rounded-2xl border border-dashed bg-background/70 p-6 text-sm text-muted-foreground"
                    >
                        No tasks have been created in this range yet.
                    </div>
                </DashboardCard>
            </div>
        </div>
    </div>
</template>
