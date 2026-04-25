<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import InputError from '@/components/form/InputError.vue';
import RichTextEditor from '@/components/richtext/RichTextEditor.vue';
import { trimStoredRichText } from '@/components/richtext/storage';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { taskInputLikeClass } from '@/lib/tasks';
import type { TaskMember, TaskProject, TaskStatusOption } from '@/types';

type Props = {
    project: Pick<TaskProject, 'id' | 'name'>;
    members: TaskMember[];
    statuses: TaskStatusOption[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const createTaskOpen = ref(false);
const createTaskFormKey = ref(0);
const createDescription = ref('');
const selectedStatus = ref('planned');
const unassignedAssigneeValue = 'unassigned';
const selectedAssignee = ref(unassignedAssigneeValue);

defineExpose({
    createTaskOpen,
    createTaskFormKey,
});
</script>

<template>
    <Dialog v-model:open="createTaskOpen">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>
        <DialogContent>
            <Form
                :key="createTaskFormKey"
                v-bind="TaskController.store.form(currentTeamSlug)"
                reset-on-success
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="
                    createTaskOpen = false;
                    selectedStatus = 'planned';
                    selectedAssignee = unassignedAssigneeValue;
                    createDescription = '';
                    createTaskFormKey++;
                "
            >
                <DialogHeader>
                    <DialogTitle>Create task</DialogTitle>
                    <DialogDescription>
                        Add a new task to {{ project.name }}.
                    </DialogDescription>
                </DialogHeader>

                <input name="project_id" type="hidden" :value="project.id" />
                <input name="position" type="hidden" value="0" />

                <div class="grid gap-2">
                    <Label for="task-title">Title</Label>
                    <Input
                        id="task-title"
                        name="title"
                        placeholder="Ship project overview page"
                        required
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label class="mb-1">Description</Label>
                    <input
                        name="description"
                        type="hidden"
                        :value="trimStoredRichText(createDescription)"
                    />
                    <RichTextEditor
                        :key="createTaskFormKey"
                        v-model="createDescription"
                        :editable="true"
                        placeholder="Add enough detail for the next person to pick this up."
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label>Status</Label>
                    <Select v-model="selectedStatus">
                        <input
                            name="status"
                            type="hidden"
                            :value="selectedStatus"
                        />
                        <SelectTrigger :class="taskInputLikeClass">
                            <SelectValue placeholder="Select a status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in props.statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label>Assignee</Label>
                    <Select v-model="selectedAssignee">
                        <input
                            name="assigned_to"
                            type="hidden"
                            :value="
                                selectedAssignee === unassignedAssigneeValue
                                    ? ''
                                    : selectedAssignee
                            "
                        />
                        <SelectTrigger :class="taskInputLikeClass">
                            <SelectValue placeholder="Select an assignee" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="unassignedAssigneeValue">
                                Unassigned
                            </SelectItem>
                            <SelectItem
                                v-for="member in members"
                                :key="member.id"
                                :value="member.id.toString()"
                            >
                                {{ member.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.assigned_to" />
                </div>

                <div class="grid gap-2">
                    <Label for="task-due-date">Due date</Label>
                    <Input
                        id="task-due-date"
                        name="due_at"
                        type="date"
                        :class="taskInputLikeClass"
                        class="w-fit"
                    />
                    <InputError :message="errors.due_at" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing"
                        >Create task</Button
                    >
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
