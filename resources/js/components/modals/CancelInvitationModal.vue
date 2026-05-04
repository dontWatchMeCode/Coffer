<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import { DialogDescription } from '@/components/ui/dialog';
import { destroy as destroyInvitation } from '@/routes/teams/invitations';
import type { Team, TeamInvitation } from '@/types';

type Props = {
    team: Team;
    invitation: TeamInvitation | null;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const processing = ref(false);

const cancelInvitation = () => {
    if (!props.invitation) {
        return;
    }

    router.visit(destroyInvitation([props.team.slug, props.invitation.code]), {
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
    <ConfirmDeleteDialog
        :open="props.open"
        title="Cancel invitation"
        cancel-label="Keep invitation"
        cancel-variant="secondary"
        confirm-label="Cancel invitation"
        confirm-data-testid="cancel-invitation-confirm"
        :processing="processing"
        @update:open="emit('update:open', $event)"
        @confirm="cancelInvitation"
    >
        <template #description>
            <DialogDescription v-if="props.invitation">
                Are you sure you want to cancel the invitation for
                <span class="font-semibold text-foreground">{{
                    props.invitation.email
                }}</span
                >?
            </DialogDescription>
        </template>
    </ConfirmDeleteDialog>
</template>
