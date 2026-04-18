<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Team } from '@/types';

type Props = {
    team: Team;
};

defineProps<Props>();

const emit = defineEmits<{
    deleteTeam: [];
}>();
</script>

<template>
    <div class="space-y-6">
        <h3 class="text-sm font-medium">Delete team</h3>
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">
                    Please proceed with caution, this cannot be undone.
                </p>
            </div>
            <Button
                data-test="delete-team-button"
                variant="destructive"
                @click="emit('deleteTeam')"
                >Delete team</Button
            >
        </div>
    </div>
</template>
