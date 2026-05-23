<script setup lang="ts">
import {
    Activity,
    Ban,
    Check,
    CircleHelp,
    Clock,
    Flag,
    MessageCircle,
    Trash2,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    getTaskStatusMeta,
    formatRelativeTime,
    formatTimeEstimate,
} from '@/lib/tasks';
import type { TaskItem, TaskProject, TaskStatusOption } from '@/types';

type Props = {
    visibleTasks: TaskItem[];
    project: Pick<TaskProject, 'id' | 'name'>;
    statuses: TaskStatusOption[];
    openTask: (task: TaskItem) => void;
    updateTaskStatus: (task: TaskItem, status: string) => void;
};

defineProps<Props>();

const statusIcons = {
    flag: Flag,
    activity: Activity,
    ban: Ban,
    check: Check,
    trash: Trash2,
    help: CircleHelp,
};
</script>

<template>
    <div
        v-if="visibleTasks.length === 0"
        class="py-8 text-center text-sm text-muted-foreground"
    >
        No open tasks. Enable "Show completed & dropped" to see archived tasks.
    </div>

    <TooltipProvider :delay-duration="300">
        <div
            v-for="task in visibleTasks"
            :key="task.id"
            class="group relative mx-2 flex cursor-pointer items-center gap-3 rounded-2xl px-3 py-2 transition-colors hover:bg-muted/30"
            role="link"
            :tabindex="0"
            @click="openTask(task)"
            @keydown.enter.space.prevent="openTask(task)"
        >
            <Tooltip>
                <TooltipTrigger as-child>
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            class="cursor-pointer border-0 bg-transparent p-0"
                            @click.stop
                        >
                            <component
                                :is="
                                    statusIcons[
                                        getTaskStatusMeta(task.status)
                                            .icon as keyof typeof statusIcons
                                    ]
                                "
                                class="h-5 w-5"
                                :class="
                                    getTaskStatusMeta(task.status).badgeColor
                                "
                            />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem
                                v-for="statusOption in statuses"
                                :key="statusOption.value"
                                @click="
                                    updateTaskStatus(task, statusOption.value)
                                "
                            >
                                <span class="flex items-center gap-2">
                                    <component
                                        :is="
                                            statusIcons[
                                                getTaskStatusMeta(
                                                    statusOption.value,
                                                )
                                                    .icon as keyof typeof statusIcons
                                            ]
                                        "
                                        class="h-4 w-4"
                                        :class="
                                            getTaskStatusMeta(
                                                statusOption.value,
                                            ).badgeColor
                                        "
                                    />
                                    {{ statusOption.label }}
                                </span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </TooltipTrigger>
                <TooltipContent>
                    <p>
                        {{ getTaskStatusMeta(task.status).label }}
                    </p>
                </TooltipContent>
            </Tooltip>

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="truncate text-sm font-medium">{{
                        task.title
                    }}</span>
                </div>
                <div
                    class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <span>{{ project.name }} #{{ task.id }}</span>
                    <span class="text-border">•</span>
                    <span v-if="task.creatorName">
                        by {{ task.creatorName }}
                    </span>
                    <span v-if="task.completedAt" class="text-border">•</span>
                    <span v-if="task.completedAt">
                        {{ formatRelativeTime(task.completedAt) }}
                    </span>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <div class="h-1.5 w-16 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary transition-all"
                        :style="{
                            width: `${task.progress}%`,
                        }"
                    />
                </div>
                <span
                    class="w-8 text-right text-sm text-muted-foreground tabular-nums"
                >
                    {{ task.progress }}%
                </span>
            </div>

            <div
                v-if="task.timeEstimate"
                class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground"
            >
                <Clock class="h-3.5 w-3.5" />
                <span>{{ formatTimeEstimate(task.timeEstimate) }}</span>
            </div>

            <div
                v-if="
                    task.commentsCount !== undefined && task.commentsCount > 0
                "
                class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground"
            >
                <MessageCircle class="h-3.5 w-3.5" />
                <span>{{ task.commentsCount }}</span>
            </div>
        </div>
    </TooltipProvider>
</template>
