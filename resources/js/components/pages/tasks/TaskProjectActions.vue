<script setup lang="ts">
import type { AcceptableValue } from 'reka-ui';
import DeleteTaskDialog from '@/components/pages/tasks/DeleteTaskDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';

type Props = {
    selectedProjectId: string;
    projects: { id: number; name: string }[];
    dueDate: string;
    showActions: boolean;
    taskTitle: string;
};

defineProps<Props>();

const emit = defineEmits<{
    'update-project': [value: AcceptableValue];
    'update-due-date': [value: string];
    'delete-task': [];
}>();

const taskDeleteDialogOpen = defineModel<boolean>('taskDeleteDialogOpen', {
    required: true,
});
</script>

<template>
    <div class="space-y-3">
        <div class="grid gap-1.5">
            <Label
                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >Project</Label
            >
            <Select
                :model-value="selectedProjectId"
                @update:model-value="emit('update-project', $event)"
            >
                <SelectTrigger size="sm" class="h-8 !w-full text-sm">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="project in projects"
                        :key="project.id"
                        :value="project.id.toString()"
                    >
                        {{ project.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="grid gap-1.5">
            <Label
                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >Due date</Label
            >
            <Input
                type="date"
                :model-value="dueDate"
                class="h-8 w-full text-sm"
                @update:model-value="emit('update-due-date', String($event))"
            />
        </div>
    </div>

    <Separator v-if="showActions" />

    <div v-if="showActions" class="space-y-2">
        <Button
            variant="outline"
            size="sm"
            class="w-full cursor-pointer justify-start gap-2 text-destructive hover:bg-destructive hover:text-destructive-foreground"
            @click="taskDeleteDialogOpen = true"
        >
            Delete task
        </Button>

        <DeleteTaskDialog
            v-model:open="taskDeleteDialogOpen"
            :task-title="taskTitle"
            @confirm="emit('delete-task')"
        />
    </div>
</template>
