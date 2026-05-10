<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ListPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateSubscriptionDialog from '@/components/pages/subscriptions/CreateSubscriptionDialog.vue';
import DeleteSubscriptionDialog from '@/components/pages/subscriptions/DeleteSubscriptionDialog.vue';
import SubscriptionList from '@/components/pages/subscriptions/SubscriptionList.vue';
import { Button } from '@/components/ui/button';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as subscriptionsIndex,
    show as showSubscription,
} from '@/routes/team/subscriptions';
import type { SubscriptionItem, Team } from '@/types';

type Props = {
    subscriptions: SubscriptionItem[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const searchQuery = ref('');

const filteredSubscriptions = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.subscriptions;
    }

    const q = searchQuery.value.toLowerCase();

    return props.subscriptions.filter(
        (s) =>
            s.name?.toLowerCase().includes(q) ||
            s.description?.toLowerCase().includes(q) ||
            s.category?.toLowerCase().includes(q),
    );
});

function navigateToSubscription(subscription: SubscriptionItem): void {
    router.visit(
        showSubscription({
            current_team: currentTeamSlug.value,
            subscription: subscription.id,
        }).url,
    );
}

const createDialogRef = ref<InstanceType<
    typeof CreateSubscriptionDialog
> | null>(null);
const deleteDialogRef = ref<InstanceType<
    typeof DeleteSubscriptionDialog
> | null>(null);

function openCreateDialog(): void {
    if (createDialogRef.value) {
        createDialogRef.value.createDialogOpen = true;
    }
}

function openDeleteDialog(subscription: SubscriptionItem): void {
    deleteDialogRef.value?.openDeleteDialog(subscription);
}

const { viewMode } = useViewMode();

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Subscriptions',
                href: subscriptionsIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Subscriptions" />

    <PageHeader
        title="Subscriptions"
        description="Track recurring subscriptions and services."
    />

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <SearchInput
                    v-model="searchQuery"
                    data-testid="subscriptions-search-input"
                    placeholder="Search subscriptions..."
                />
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-end gap-2">
                    <CreateSubscriptionDialog ref="createDialogRef">
                        <template #trigger>
                            <Button
                                size="icon"
                                title="Create subscription"
                                class="cursor-pointer"
                            >
                                <ListPlus class="h-4 w-4" />
                            </Button>
                        </template>
                    </CreateSubscriptionDialog>

                    <ViewModeToggle
                        v-if="filteredSubscriptions.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <SubscriptionList
                    :filtered-subscriptions="filteredSubscriptions"
                    :search-query="searchQuery"
                    :navigate-to-subscription="navigateToSubscription"
                    :open-delete-dialog="openDeleteDialog"
                    :open-create-dialog="openCreateDialog"
                    :view-mode="viewMode"
                />
            </div>
        </div>
    </div>

    <DeleteSubscriptionDialog ref="deleteDialogRef" />
</template>
