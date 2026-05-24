<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateSubscriptionDialog from '@/components/pages/subscriptions/CreateSubscriptionDialog.vue';
import DeleteSubscriptionDialog from '@/components/pages/subscriptions/DeleteSubscriptionDialog.vue';
import SubscriptionList from '@/components/pages/subscriptions/SubscriptionList.vue';
import { Button } from '@/components/ui/button';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as subscriptionsIndex,
    show as showSubscription,
    trash as subscriptionsTrash,
} from '@/routes/team/subscriptions';
import type {
    PaginatedData,
    SubscriptionCategory,
    SubscriptionItem,
    Team,
} from '@/types';

type Props = {
    subscriptions: PaginatedData<SubscriptionItem>;
    categories: SubscriptionCategory[];
    categoryCandidatesUrl?: string;
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    subscriptionsIndex(currentTeamSlug.value).url,
    'subscriptions',
);

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

const { viewMode } = useViewMode('subscriptions');

defineOptions({
    inheritAttrs: false,
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
                    <Button
                        variant="outline"
                        size="icon"
                        title="Trash"
                        as-child
                    >
                        <Link :href="subscriptionsTrash(currentTeamSlug).url">
                            <Trash2 class="h-4 w-4" />
                        </Link>
                    </Button>

                    <CreateSubscriptionDialog
                        ref="createDialogRef"
                        :categories="categories"
                        :category-candidates-url="categoryCandidatesUrl ?? ''"
                    >
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
                        v-if="props.subscriptions.data.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <InfiniteScroll data="subscriptions">
                    <SubscriptionList
                        :filtered-subscriptions="props.subscriptions.data"
                        :search-query="searchQuery"
                        :navigate-to-subscription="navigateToSubscription"
                        :open-delete-dialog="openDeleteDialog"
                        :open-create-dialog="openCreateDialog"
                        :view-mode="viewMode"
                    />
                </InfiniteScroll>
            </div>
        </div>
    </div>

    <DeleteSubscriptionDialog ref="deleteDialogRef" />
</template>
