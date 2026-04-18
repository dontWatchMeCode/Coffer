<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    team: Team;
};

defineProps<Props>();
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
