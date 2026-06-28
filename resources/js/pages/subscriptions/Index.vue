<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, PieChart, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import CreateSubscriptionDialog from '@/components/pages/subscriptions/CreateSubscriptionDialog.vue';
import DeleteSubscriptionDialog from '@/components/pages/subscriptions/DeleteSubscriptionDialog.vue';
import SubscriptionDetailOverlay from '@/components/pages/subscriptions/SubscriptionDetailOverlay.vue';
import SubscriptionList from '@/components/pages/subscriptions/SubscriptionList.vue';
import { Button } from '@/components/ui/button';
import { useListDetailOverlay } from '@/composables/useListDetailOverlay';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as subscriptionsIndex,
    show as showSubscription,
    trash as subscriptionsTrash,
    insights as subscriptionsInsights,
} from '@/routes/team/subscriptions';
import type {
    ActivityHistoryConfig,
    PaginatedData,
    SubscriptionCategory,
    SubscriptionItem,
    Team,
} from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    subscriptions: PaginatedData<SubscriptionItem>;
    categories: SubscriptionCategory[];
    categoryCandidatesUrl?: string;
    subscription?: SubscriptionItem;
    recordLinks?: {
        links: LinkRecord[];
        context: LinkContext;
        endpoints: LinkEndpoints;
    } | null;
    recordTags?: {
        tags: RecordTag[];
        context: TagContext;
        endpoints: TagEndpoints;
    } | null;
    activityHistory?: ActivityHistoryConfig;
};

type SubscriptionPageProps = PageProps & Partial<Props>;

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    subscriptionsIndex(currentTeamSlug.value).url,
    'subscriptions',
);

const {
    closeDetail,
    rememberSavedItem,
    getPendingSavedItem,
    clearPendingSavedItem,
} = useListDetailOverlay(
    'subscriptions',
    currentTeamSlug.value,
    Boolean(props.subscriptions),
);

function navigateToSubscription(subscription: SubscriptionItem): void {
    router.visit(
        showSubscription({
            current_team: currentTeamSlug.value,
            subscription: subscription.id,
        }).url,
        {
            only: [
                'subscription',
                'recordLinks',
                'recordTags',
                'activityHistory',
            ],
            preserveScroll: true,
        },
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

function replaceLoadedSubscription(subscription: SubscriptionItem): boolean {
    if (!props.subscriptions?.data.some((s) => s.id === subscription.id)) {
        return false;
    }

    router.replaceProp<SubscriptionPageProps>(
        'subscriptions.data',
        (subs: unknown) => {
            if (!Array.isArray(subs)) {
                return subs;
            }

            return subs.map((s) =>
                s.id === subscription.id ? subscription : s,
            );
        },
    );

    return true;
}

function applyPendingSavedSubscription(): void {
    if (props.subscription || !props.subscriptions) {
        return;
    }

    const subscription = getPendingSavedItem<
        SubscriptionItem & { id: number }
    >();

    if (!subscription || typeof subscription.id !== 'number') {
        clearPendingSavedItem();

        return;
    }

    replaceLoadedSubscription(subscription);
    clearPendingSavedItem();
}

function closeSubscription(): void {
    closeDetail(subscriptionsIndex(currentTeamSlug.value).url);
}

function onSaved(subscription: SubscriptionItem): void {
    rememberSavedItem(subscription);
    replaceLoadedSubscription(subscription);
}

watch(
    () => [props.subscription?.id, props.subscriptions?.data],
    () => applyPendingSavedSubscription(),
    { immediate: true, flush: 'post' },
);

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: {
        currentTeam?: Team | null;
        subscription?: { id: number; name: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Subscriptions',
                href: subscriptionsIndex(pageProps.currentTeam?.slug).url,
            },
            ...(pageProps.subscription
                ? [{ title: pageProps.subscription.name }]
                : []),
        ],
    }),
});
</script>

<template>
    <Head
        :title="props.subscription ? props.subscription.name : 'Subscriptions'"
    />

    <div v-if="props.subscriptions && !props.subscription">
        <PageHeader
            title="Subscriptions"
            description="Track recurring subscriptions and services."
        >
            <template #actions>
                <Button
                    variant="outline"
                    size="sm"
                    title="Insights"
                    as-child
                    class="cursor-pointer gap-1.5"
                >
                    <Link :href="subscriptionsInsights(currentTeamSlug).url">
                        <PieChart class="h-3.5 w-3.5" />
                        Insights
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="min-w-0 flex-1 px-4 py-6">
            <div class="mx-auto w-full max-w-7xl">
                <div class="mb-4 flex items-center justify-end gap-3">
                    <SearchInput
                        v-model="searchQuery"
                        data-testid="subscriptions-search-input"
                        placeholder="Search subscriptions..."
                    />
                </div>

                <div class="min-w-0 space-y-4">
                    <div class="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            title="Trash"
                            as-child
                        >
                            <Link
                                :href="subscriptionsTrash(currentTeamSlug).url"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Link>
                        </Button>

                        <CreateSubscriptionDialog
                            ref="createDialogRef"
                            :categories="categories"
                            :category-candidates-url="
                                categoryCandidatesUrl ?? ''
                            "
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

                    <InfiniteScroll data="subscriptions" :buffer="1200">
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
    </div>

    <SubscriptionDetailOverlay
        v-if="props.subscription"
        :subscription="props.subscription"
        :record-links="props.recordLinks"
        :record-tags="props.recordTags"
        :activity-history="props.activityHistory"
        :categories="props.categories"
        :category-candidates-url="props.categoryCandidatesUrl"
        @close="closeSubscription"
        @saved="onSaved"
    />

    <DeleteSubscriptionDialog ref="deleteDialogRef" />
</template>
