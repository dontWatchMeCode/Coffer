<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import { Button } from '@/components/ui/button';
import { DialogDescription } from '@/components/ui/dialog';

export type TrashRecord = {
    id: number;
    title: string;
    subtitle?: string | null;
    deletedAt?: string | null;
};

type Props = {
    records: TrashRecord[];
    searchQuery: string;
    moduleName: string;
    restoreUrl: (record: TrashRecord) => string;
    forceDeleteUrl: (record: TrashRecord) => string;
};

const props = defineProps<Props>();

const deletingRecord = ref<TrashRecord | null>(null);
const deleteDialogOpen = ref(false);

const emptyTitle = computed(() =>
    props.searchQuery
        ? `No deleted ${props.moduleName.toLowerCase()} match your search.`
        : `No deleted ${props.moduleName.toLowerCase()}.`,
);

function formatDeletedAt(value?: string | null): string {
    if (!value) {
        return 'Deleted recently';
    }

    return `Deleted ${new Date(value).toLocaleString()}`;
}

function restore(record: TrashRecord): void {
    router.patch(props.restoreUrl(record), {}, { preserveScroll: true });
}

function openForceDeleteDialog(record: TrashRecord): void {
    deletingRecord.value = record;
    deleteDialogOpen.value = true;
}

function forceDelete(): void {
    if (!deletingRecord.value) {
        return;
    }

    const record = deletingRecord.value;
    deletingRecord.value = null;
    deleteDialogOpen.value = false;

    router.delete(props.forceDeleteUrl(record), { preserveScroll: true });
}
</script>

<template>
    <ListContainer v-if="records.length > 0" layout="list">
        <ListItem v-for="record in records" :key="record.id">
            <div class="flex w-full min-w-0 items-center gap-4 overflow-hidden">
                <div class="min-w-0 flex-1">
                    <p class="font-medium [overflow-wrap:anywhere]">
                        {{ record.title }}
                    </p>
                    <p
                        v-if="record.subtitle"
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground"
                    >
                        {{ record.subtitle }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatDeletedAt(record.deletedAt) }}
                    </p>
                </div>

                <ListItemActions>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="gap-1.5"
                        @click.stop="restore(record)"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        Restore
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="gap-1.5 text-destructive hover:text-destructive"
                        @click.stop="openForceDeleteDialog(record)"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        Delete forever
                    </Button>
                </ListItemActions>
            </div>
        </ListItem>
    </ListContainer>

    <EmptyState
        v-else
        :title="emptyTitle"
        description="Moved items appear here."
    >
        <template #icon>
            <Trash2 class="h-12 w-12" />
        </template>
    </EmptyState>

    <ConfirmDeleteDialog
        v-model:open="deleteDialogOpen"
        title="Delete Permanently"
        confirm-label="Delete forever"
        :confirm-icon="Trash2"
        @confirm="forceDelete"
    >
        <template #description>
            <DialogDescription>
                Permanently delete
                <span class="font-semibold text-foreground">
                    {{ deletingRecord?.title }}</span
                >? This action cannot be undone.
            </DialogDescription>
        </template>
    </ConfirmDeleteDialog>
</template>
