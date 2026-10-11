<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Layers3,
    ListTodo,
} from 'lucide-vue-next';
import { computed } from 'vue';
import KpiCard from '@/components/dashboard/KpiCard.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard as rootDashboard } from '@/routes';
import { dashboard as teamDashboard } from '@/routes/team';
import type { Team } from '@/types';

type DashboardTask = {
    id: number;
    title: string;
    status: string;
    project: string | null;
    dueAt: string | null;
    isOverdue: boolean;
    url: string;
};

type DashboardEvent = {
    id: number;
    title: string;
    time: string | null;
    url: string;
};

type ActivityItem = {
    id: number;
    type: string;
    title: string;
    subtitle: string | null;
    updatedAt: string | null;
    url: string;
};

type DashboardData = {
    stats: {
        openTasks: number;
        dueToday: number;
        overdue: number;
        eventsToday: number;
        activeProjects: number;
    };
    today: {
        tasks: DashboardTask[];
        events: DashboardEvent[];
    };
    recent: {
        activity: ActivityItem[];
    };
};

const props = defineProps<{
    dashboard: DashboardData | null;
    currentTeam?: Team | null;
}>();

const formatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
});

const timeFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
});

const stats = computed(() => {
    const data = props.dashboard?.stats;

    return [
        {
            label: 'Open tasks',
            value: data?.openTasks ?? 0,
            detail: `${data?.overdue ?? 0} overdue`,
            icon: ListTodo,
        },
        {
            label: 'Due today',
            value: data?.dueToday ?? 0,
            detail: 'tasks due today',
            icon: CalendarDays,
        },
        {
            label: 'Projects',
            value: data?.activeProjects ?? 0,
            detail: 'active spaces',
            icon: Layers3,
        },
        {
            label: 'Calendar',
            value: data?.eventsToday ?? 0,
            detail: 'events today',
            icon: CalendarClock,
        },
    ];
});

const hasTodayWork = computed(
    () =>
        (props.dashboard?.today.tasks.length ?? 0) > 0 ||
        (props.dashboard?.today.events.length ?? 0) > 0,
);

function formatDate(value: string | null): string {
    if (!value) {
        return 'No date';
    }

    return formatter.format(new Date(value));
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Never';
    }

    return timeFormatter.format(new Date(value));
}

function formatStatus(status: string): string {
    return status.replace(/_/g, ' ');
}

defineOptions({
    inheritAttrs: false,
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: layoutProps.currentTeam
                    ? teamDashboard(layoutProps.currentTeam.slug).url
                    : rootDashboard().url,
            },
        ],
    }),
});
</script>

<template>
    <div class="flex min-w-0 flex-1 flex-col">
        <Head title="Dashboard" />

        <PageHeader
            title="Dashboard"
            :subtitle="currentTeam ? `· ${currentTeam.name}` : undefined"
            description="Today's priorities and recent team activity."
        />

        <div class="flex-1 py-5">
            <div class="mx-auto max-w-7xl">
                <div v-if="dashboard" class="flex flex-col gap-4">
                    <section
                        aria-label="Workspace summary"
                        class="grid gap-4 min-[1081px]:grid-cols-4"
                    >
                        <KpiCard
                            v-for="item in stats"
                            :key="item.label"
                            :label="item.label"
                            :value="item.value"
                            :detail="item.detail"
                            :icon="item.icon"
                        />
                    </section>

                    <section class="grid gap-4 min-[1081px]:grid-cols-12">
                        <Card
                            class="min-w-0 self-start overflow-hidden py-0 min-[1081px]:col-span-7"
                        >
                            <CardHeader class="flex">
                                <CardTitle
                                    class="flex items-center gap-2 text-xs"
                                >
                                    <Clock3
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    Today
                                </CardTitle>
                            </CardHeader>
                            <CardContent
                                class="grid gap-4 p-3 min-[1400px]:grid-cols-2"
                            >
                                <div class="space-y-3">
                                    <div class="text-sm font-medium">
                                        Due tasks
                                    </div>
                                    <div
                                        v-if="dashboard.today.tasks.length"
                                        class="divide-y divide-dashed"
                                    >
                                        <Link
                                            v-for="task in dashboard.today
                                                .tasks"
                                            :key="task.id"
                                            :href="task.url"
                                            class="group block py-2 transition-colors hover:bg-accent/30"
                                        >
                                            <div
                                                class="flex flex-wrap items-start justify-between gap-3"
                                            >
                                                <div class="min-w-0">
                                                    <p
                                                        class="text-sm font-medium wrap-anywhere"
                                                    >
                                                        {{ task.title }}
                                                    </p>
                                                    <p
                                                        class="mt-1 text-xs text-muted-foreground"
                                                    >
                                                        {{
                                                            task.project ??
                                                            'No project'
                                                        }}
                                                    </p>
                                                </div>
                                                <Badge
                                                    :variant="
                                                        task.isOverdue
                                                            ? 'destructive'
                                                            : 'secondary'
                                                    "
                                                    class="capitalize"
                                                >
                                                    {{
                                                        task.isOverdue
                                                            ? 'Overdue · '
                                                            : ''
                                                    }}{{
                                                        formatDate(task.dueAt)
                                                    }}
                                                </Badge>
                                            </div>
                                            <div
                                                class="mt-2 flex items-center justify-between text-xs text-muted-foreground"
                                            >
                                                <span class="capitalize">{{
                                                    formatStatus(task.status)
                                                }}</span>
                                                <ArrowRight
                                                    class="h-3.5 w-3.5 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100"
                                                />
                                            </div>
                                        </Link>
                                    </div>
                                    <div
                                        v-else
                                        class="border border-dashed bg-background/70 p-4 text-sm text-muted-foreground"
                                    >
                                        No tasks due today or overdue.
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="text-sm font-medium">
                                        Calendar
                                    </div>
                                    <div
                                        v-if="dashboard.today.events.length"
                                        class="divide-y divide-dashed"
                                    >
                                        <Link
                                            v-for="event in dashboard.today
                                                .events"
                                            :key="event.id"
                                            :href="event.url"
                                            class="group flex items-center justify-between gap-3 py-2 transition-colors hover:bg-accent/30"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="text-sm font-medium wrap-anywhere"
                                                >
                                                    {{ event.title }}
                                                </p>
                                                <p
                                                    class="mt-1 text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        event.time ?? 'All day'
                                                    }}
                                                </p>
                                            </div>
                                            <CalendarDays
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </Link>
                                    </div>
                                    <div
                                        v-else
                                        class="border border-dashed bg-background/70 p-4 text-sm text-muted-foreground"
                                    >
                                        No events scheduled today.
                                    </div>
                                </div>

                                <div
                                    v-if="!hasTodayWork"
                                    class="border border-dashed border-success/40 bg-success/10 p-3 text-sm text-success min-[1400px]:col-span-2"
                                >
                                    <CheckCircle2 class="mr-2 inline h-4 w-4" />
                                    Clear day. Good time to capture notes or
                                    clean up projects.
                                </div>
                            </CardContent>
                        </Card>
                        <Card
                            class="min-w-0 self-start py-0 min-[1081px]:col-span-5"
                        >
                            <CardHeader class="flex">
                                <CardTitle
                                    class="flex items-center gap-2 text-xs"
                                >
                                    <Activity
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    Recent activity
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="divide-y divide-dashed p-0">
                                <Link
                                    v-for="item in dashboard.recent.activity"
                                    :key="`${item.type}-${item.id}`"
                                    :href="item.url"
                                    class="flex items-center justify-between gap-3 px-3 py-2 transition-colors hover:bg-muted/50"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <Badge variant="secondary">{{
                                                item.type
                                            }}</Badge>
                                            <p
                                                class="text-sm font-medium wrap-anywhere"
                                            >
                                                {{ item.title }}
                                            </p>
                                        </div>
                                        <p
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            {{ item.subtitle ?? 'Updated' }} ·
                                            {{ formatDateTime(item.updatedAt) }}
                                        </p>
                                    </div>
                                    <ArrowRight
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                </Link>
                                <div
                                    v-if="!dashboard.recent.activity.length"
                                    class="p-4 text-sm text-muted-foreground"
                                >
                                    Recent updates will appear here.
                                </div>
                            </CardContent>
                        </Card>
                    </section>
                </div>

                <div v-else class="flex flex-1 items-center justify-center p-6">
                    <Card class="max-w-md text-center">
                        <CardHeader>
                            <CardTitle class="text-sm text-card-foreground"
                                >No team selected</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">
                            Create or switch to a team to see dashboard
                            activity.
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
