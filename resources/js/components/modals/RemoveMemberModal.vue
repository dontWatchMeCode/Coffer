<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import { DialogDescription } from '@/components/ui/dialog';
import { destroy as destroyMember } from '@/routes/teams/members';
import type { Team, TeamMember } from '@/types';

type Props = {
    team: Team;
    member: TeamMember | null;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const processing = ref(false);

const removeMember = () => {
    if (!props.member) {
        return;
    }

    router.visit(destroyMember([props.team.slug, props.member.id]), {
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
    <ConfirmDeleteDialog
        :open="props.open"
        title="Remove team member"
        confirm-label="Remove member"
        confirm-data-testid="remove-member-confirm"
        :confirm-icon="Trash2"
        :processing="processing"
        @update:open="emit('update:open', $event)"
        @confirm="removeMember"
    >
        <template #description>
            <DialogDescription v-if="props.member">
                Are you sure you want to remove
                <span class="font-semibold text-foreground">{{
                    props.member.name
                }}</span>
                from this team?
            </DialogDescription>
        </template>
    </ConfirmDeleteDialog>
</template>
