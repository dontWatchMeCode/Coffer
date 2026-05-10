<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Layers3,
    ListTodo,
} from 'lucide-vue-next';
import { computed } from 'vue';
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
            detail: `${data?.eventsToday ?? 0} events today`,
            icon: CalendarDays,
        },
        {
            label: 'Projects',
            value: data?.activeProjects ?? 0,
            detail: 'active spaces',
            icon: Layers3,
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
    <Head title="Dashboard" />

    <PageHeader
        title="Dashboard"
        :subtitle="currentTeam ? `· ${currentTeam.name}` : undefined"
        description="Today's priorities and recent team activity."
    />

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <main v-if="dashboard" class="flex flex-col gap-5">
                <section class="rounded-2xl border bg-card/70 px-4 py-5">
                    <div
                        class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                    >
                        <div class="space-y-1">
                            <h2 class="text-base font-semibold tracking-tight">
                                Command center
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Today’s priorities and recent activity.
                            </p>
                        </div>

                        <div
                            class="flex max-w-full flex-wrap gap-x-8 gap-y-4 xl:justify-end"
                        >
                            <div
                                v-for="item in stats"
                                :key="item.label"
                                class="flex w-max max-w-full min-w-0 items-start gap-3 border-l pl-4 first:border-l-0 first:pl-0"
                            >
                                <div
                                    class="mt-1 rounded-lg bg-muted p-1.5 text-muted-foreground"
                                >
                                    <component
                                        :is="item.icon"
                                        class="h-4 w-4"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="text-2xl leading-none font-semibold"
                                    >
                                        {{ item.value }}
                                    </div>
                                    <div
                                        class="mt-1 text-xs leading-snug text-muted-foreground"
                                    >
                                        {{ item.label }} · {{ item.detail }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-5 xl:grid-cols-12">
                    <Card class="self-start overflow-hidden py-0 xl:col-span-7">
                        <CardHeader class="border-b py-5 flex">
                            <CardTitle
                                class="flex items-center gap-2 text-base"
                            >
                                <Clock3 class="h-4 w-4 text-muted-foreground" />
                                Today
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4 p-4 lg:grid-cols-2">
                            <div class="space-y-3">
                                <div class="text-sm font-medium">Due tasks</div>
                                <div
                                    v-if="dashboard.today.tasks.length"
                                    class="space-y-2"
                                >
                                    <Link
                                        v-for="task in dashboard.today.tasks"
                                        :key="task.id"
                                        :href="task.url"
                                        class="group block rounded-2xl border bg-background p-3 transition hover:border-primary/40 hover:bg-primary/5"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-medium"
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
                                                {{ formatDate(task.dueAt) }}
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
                                    class="rounded-2xl border border-dashed bg-background/70 p-4 text-sm text-muted-foreground"
                                >
                                    No tasks due today or overdue.
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="text-sm font-medium">Calendar</div>
                                <div
                                    v-if="dashboard.today.events.length"
                                    class="space-y-2"
                                >
                                    <Link
                                        v-for="event in dashboard.today.events"
                                        :key="event.id"
                                        :href="event.url"
                                        class="group flex items-center justify-between gap-3 rounded-2xl border bg-background p-3 transition hover:border-primary/40 hover:bg-primary/5"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-medium"
                                            >
                                                {{ event.title }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ event.time ?? 'All day' }}
                                            </p>
                                        </div>
                                        <CalendarDays
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                    </Link>
                                </div>
                                <div
                                    v-else
                                    class="rounded-2xl border border-dashed bg-background/70 p-4 text-sm text-muted-foreground"
                                >
                                    No events scheduled today.
                                </div>
                            </div>

                            <div
                                v-if="!hasTodayWork"
                                class="rounded-2xl bg-emerald-500/10 p-4 text-sm text-emerald-700 lg:col-span-2 dark:text-emerald-300"
                            >
                                <CheckCircle2 class="mr-2 inline h-4 w-4" />
                                Clear day. Good time to capture notes or clean
                                up projects.
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="self-start py-0 xl:col-span-5">
                        <CardHeader class="border-b py-5 flex">
                            <CardTitle
                                class="flex items-center gap-2 text-base"
                            >
                                <Clock3 class="h-4 w-4 text-muted-foreground" />
                                Recent activity
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="divide-y p-0">
                            <Link
                                v-for="item in dashboard.recent.activity"
                                :key="`${item.type}-${item.id}`"
                                :href="item.url"
                                class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-muted/50"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <Badge variant="secondary">{{
                                            item.type
                                        }}</Badge>
                                        <p class="truncate text-sm font-medium">
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
            </main>

            <div v-else class="flex flex-1 items-center justify-center p-6">
                <Card class="max-w-md text-center">
                    <CardHeader>
                        <CardTitle>No team selected</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        Create or switch to a team to see dashboard activity.
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
