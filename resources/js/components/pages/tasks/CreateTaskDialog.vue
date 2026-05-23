<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import InputError from '@/components/form/InputError.vue';
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
import type { TaskProject } from '@/types';

type Props = {
    project: Pick<TaskProject, 'id' | 'name'>;
};

defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const createTaskOpen = ref(false);
const createTaskFormKey = ref(0);

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

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing"
                        >Create task</Button
                    >
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
