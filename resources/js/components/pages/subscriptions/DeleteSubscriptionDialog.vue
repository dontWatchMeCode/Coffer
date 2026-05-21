<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import { DialogDescription } from '@/components/ui/dialog';
import { destroy as deleteSubscription } from '@/routes/team/subscriptions';
import type { SubscriptionItem } from '@/types';

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const deleteDialogOpen = ref(false);
const deletingSubscription = ref<SubscriptionItem | null>(null);

function openDeleteDialog(subscription: SubscriptionItem): void {
    deletingSubscription.value = subscription;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!deletingSubscription.value) {
        return;
    }

    const subscription = deletingSubscription.value;
    deleteDialogOpen.value = false;
    deletingSubscription.value = null;

    router.delete(
        deleteSubscription({
            current_team: currentTeamSlug.value,
            subscription: subscription.id,
        }),
        { preserveScroll: true },
    );
}

defineExpose({ openDeleteDialog, deleteDialogOpen });
</script>

<template>
    <ConfirmDeleteDialog
        v-model:open="deleteDialogOpen"
        title="Move Subscription to Trash"
        confirm-label="Move to trash"
        :confirm-icon="Trash2"
        @confirm="confirmDelete"
    >
        <template #description>
            <DialogDescription>
                Are you sure you want to delete
                <span class="font-semibold text-foreground">{{
                    deletingSubscription?.name
                }}</span
                >? You can restore it from subscription trash.
            </DialogDescription>
        </template>
    </ConfirmDeleteDialog>
</template>
