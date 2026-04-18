<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/Tasks/ProjectController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { taskInputLikeClass } from '@/lib/tasks';
import type { TaskProject } from '@/types';

type Props = {
    project: Pick<TaskProject, 'id' | 'name' | 'description' | 'isArchived'>;
};

defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const projectSettingsOpen = ref(false);
const projectSettingsFormKey = ref(0);

defineExpose({
    projectSettingsOpen,
    projectSettingsFormKey,
});
</script>

<template>
    <Dialog v-model:open="projectSettingsOpen">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>
        <DialogContent>
            <Form
                :key="projectSettingsFormKey"
                v-bind="
                    ProjectController.update.form({
                        current_team: currentTeamSlug,
                        project: project.id,
                    })
                "
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="
                    projectSettingsOpen = false;
                    projectSettingsFormKey++;
                "
            >
                <DialogHeader>
                    <DialogTitle>Project settings</DialogTitle>
                    <DialogDescription>
                        Update the selected project.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="selected-project-name">Name</Label>
                    <Input
                        id="selected-project-name"
                        name="name"
                        :default-value="project.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="selected-project-description"
                        >Description</Label
                    >
                    <textarea
                        id="selected-project-description"
                        name="description"
                        :class="taskInputLikeClass"
                        rows="4"
                        :value="project.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>

                <label class="flex cursor-pointer items-center gap-2">
                    <Checkbox
                        name="archived"
                        value="1"
                        :default-value="project.isArchived"
                    />
                    <span class="text-sm">Archived</span>
                </label>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing"
                        >Save project</Button
                    >
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
