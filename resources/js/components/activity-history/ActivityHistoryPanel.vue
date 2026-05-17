<script setup lang="ts">
import { History } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type {
    ActivityHistoryConfig,
    ActivityHistoryItem,
    ActivityHistoryResponse,
} from '@/types';
import ActivityHistoryDialogContent from './ActivityHistoryDialogContent.vue';

type Props = {
    config?: ActivityHistoryConfig | null;
    activities?: ActivityHistoryItem[] | null;
    variant?: 'default' | 'compact';
    teamSlug?: string;
};

const props = withDefaults(defineProps<Props>(), {
    config: null,
    activities: null,
    variant: 'default',
    teamSlug: '',
});

const isApiMode = computed(() => !!props.config);

const open = ref(false);
const fetchedActivities = ref<ActivityHistoryItem[]>([]);
const page = ref(1);
const hasMore = ref(false);
const loading = ref(false);
const fetchError = ref(false);
const total = computed(
    () => props.config?.total ?? props.activities?.length ?? 0,
);
const displayActivities = computed(() =>
    isApiMode.value ? fetchedActivities.value : (props.activities ?? []),
);

function fetchPage(pageNum: number): void {
    if (!props.config?.subject_type) {
        return;
    }

    fetchError.value = false;

    const params = new URLSearchParams({
        subject_type: props.config.subject_type,
        subject_id: String(props.config.subject_id),
        page: String(pageNum),
    });

    fetch(`/${props.teamSlug}/activity-history?${params}`, {
        headers: { Accept: 'application/json' },
    })
        .then((r) => {
            if (!r.ok) {
                throw new Error('Failed to fetch');
            }

            return r.json();
        })
        .then((data: ActivityHistoryResponse) => {
            if (pageNum === 1) {
                fetchedActivities.value = data.activities;
            } else {
                fetchedActivities.value = [
                    ...fetchedActivities.value,
                    ...data.activities,
                ];
            }

            hasMore.value = fetchedActivities.value.length < data.total;
        })
        .catch(() => {
            fetchError.value = true;
            hasMore.value = false;
        })
        .finally(() => {
            loading.value = false;
        });
}

function retry(): void {
    reset();
    fetchPage(1);
}

function reset(): void {
    fetchedActivities.value = [];
    page.value = 1;
    hasMore.value = false;
    fetchError.value = false;
}

function onOpen(isOpen: boolean): void {
    open.value = isOpen;

    if (isOpen && isApiMode.value) {
        if (!props.config?.subject_type) {
            return;
        }

        reset();
        loading.value = true;
        fetchPage(1);
    }
}

function loadMore(): void {
    if (loading.value || !hasMore.value) {
        return;
    }

    const nextPage = page.value + 1;
    page.value = nextPage;
    loading.value = true;
    fetchPage(nextPage);
}
</script>

<template>
    <div>
        <button
            v-if="props.variant === 'default'"
            type="button"
            class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
            @click="onOpen(true)"
        >
            <History class="h-4 w-4 text-muted-foreground" />
            <span class="flex-1">Activity History</span>
            <span
                v-if="total > 0"
                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium text-muted-foreground"
            >
                {{ total }}
            </span>
        </button>

        <button
            v-else
            type="button"
            class="inline-flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
            title="Activity History"
            @click="onOpen(true)"
        >
            <History class="h-3.5 w-3.5" />
            <span
                v-if="total > 0"
                class="inline-flex items-center rounded-full border px-1.5 py-0 text-[10px] font-medium text-muted-foreground"
            >
                {{ total }}
            </span>
        </button>

        <Dialog :open="open" @update:open="onOpen">
            <DialogContent
                class="max-h-[85vh] overflow-x-hidden overflow-y-auto sm:max-w-lg"
            >
                <DialogHeader>
                    <DialogTitle>Activity History</DialogTitle>
                </DialogHeader>

                <div
                    v-if="fetchError && fetchedActivities.length === 0"
                    class="space-y-3 py-4 text-center"
                >
                    <p class="text-sm text-muted-foreground">
                        Failed to load activity history.
                    </p>
                    <Button variant="outline" size="sm" @click="retry">
                        Retry
                    </Button>
                </div>

                <ActivityHistoryDialogContent
                    v-else
                    :activities="displayActivities"
                    :total="total"
                    :has-more="isApiMode && hasMore"
                    :loading="loading"
                    :dialog-open="open"
                    @load-more="loadMore"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
