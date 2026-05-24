<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import {
    forceDestroy,
    index,
    restore,
    trash,
} from '@/routes/team/subscriptions';
import type { PaginatedData, SubscriptionItem, Team } from '@/types';

const props = defineProps<{
    subscriptions: PaginatedData<SubscriptionItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    trash(currentTeamSlug.value).url,
    'subscriptions',
);

const records = computed<TrashRecord[]>(() =>
    props.subscriptions.data.map((subscription) => ({
        id: subscription.id,
        title: subscription.name,
        subtitle: subscription.category ?? subscription.description,
        deletedAt: subscription.deletedAt,
    })),
);

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Subscriptions',
                href: index(pageProps.currentTeam?.slug).url,
            },
            { title: 'Trash', href: trash(pageProps.currentTeam?.slug).url },
        ],
    }),
});
</script>

<template>
    <TrashPage
        v-model:search-query="searchQuery"
        title="Deleted Subscriptions"
        description="Restore subscriptions or delete them permanently."
        module-name="Subscriptions"
        scroll-data="subscriptions"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to subscriptions"
        :restore-url="
            (record) =>
                restore({
                    current_team: currentTeamSlug,
                    subscription: record.id,
                }).url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({
                    current_team: currentTeamSlug,
                    subscription: record.id,
                }).url
        "
    />
</template>
