<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/form/InputError.vue';
import StatusOptionsInput from '@/components/pages/tasks/StatusOptionsInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/teams';
import type { Team, TeamFeatureOption } from '@/types';

type Props = {
    team: Team;
    teamFeatures: TeamFeatureOption[];
};

const props = defineProps<Props>();
const statusOptions = ref([...(props.team.defaultTaskStatusOptions ?? [])]);
</script>

<template>
    <Form
        v-bind="update.form(team.slug)"
        class="space-y-6"
        v-slot="{ errors, processing, recentlySuccessful }"
    >
        <div class="grid gap-2">
            <Label for="name">Team name</Label>
            <Input
                id="name"
                name="name"
                data-test="team-name-input"
                :default-value="team.name"
                required
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="default-task-status-options"
                >Default task statuses</Label
            >
            <StatusOptionsInput
                id="default-task-status-options"
                v-model="statusOptions"
                name="default_task_status_options"
                :options="statusOptions"
            />
            <p class="text-xs text-muted-foreground">
                Pick existing statuses or create new ones. New projects copy
                this list.
            </p>
            <InputError :message="errors.default_task_status_options" />
        </div>

        <div class="grid gap-3">
            <div>
                <Label>Features</Label>
                <p class="text-xs text-muted-foreground">
                    Disabled features are hidden and their routes are blocked.
                    Existing records are preserved.
                </p>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <label
                    v-for="feature in teamFeatures"
                    :key="feature.value"
                    class="flex items-center justify-between gap-3 rounded-lg border bg-card px-3 py-2 text-sm"
                >
                    <span>{{ feature.label }}</span>
                    <input
                        type="hidden"
                        :name="`feature_settings[${feature.value}]`"
                        value="0"
                    />
                    <Checkbox
                        :name="`feature_settings[${feature.value}]`"
                        value="1"
                        :default-value="
                            team.featureSettings?.[feature.value] ?? true
                        "
                    />
                </label>
            </div>
            <InputError :message="errors.feature_settings" />
        </div>

        <div class="flex items-center gap-4">
            <Button
                type="submit"
                data-test="team-save-button"
                :disabled="processing"
            >
                Save
            </Button>

            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <p v-show="recentlySuccessful" class="text-sm text-neutral-600">
                    Saved.
                </p>
            </Transition>
        </div>
    </Form>
</template>
