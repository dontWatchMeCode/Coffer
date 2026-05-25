<script setup lang="ts">
import { CreditCard, ExternalLink, Plus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ViewMode } from '@/composables/useViewMode';
import type { SubscriptionItem } from '@/types';

type Props = {
    filteredSubscriptions: SubscriptionItem[];
    searchQuery: string;
    navigateToSubscription: (subscription: SubscriptionItem) => void;
    openDeleteDialog: (subscription: SubscriptionItem) => void;
    openCreateDialog: () => void;
    viewMode: ViewMode;
};

defineProps<Props>();

function formatPrice(
    price: string | null | undefined,
    currency: string | null | undefined,
): string {
    if (!price || parseFloat(price) === 0) {
        return 'Free';
    }

    const num = parseFloat(price);
    const cur = currency || 'USD';

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: cur,
    }).format(num);
}

function formatDate(date: string | null | undefined): string {
    if (!date) {
        return '';
    }

    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatBillingCycle(cycle: string | null | undefined): string {
    if (!cycle) {
        return '';
    }

    return cycle.charAt(0).toUpperCase() + cycle.slice(1);
}
</script>

<template>
    <ListContainer v-if="filteredSubscriptions.length > 0" :layout="viewMode">
        <ListItem
            v-for="subscription in filteredSubscriptions"
            :key="subscription.id"
            @click="navigateToSubscription(subscription)"
        >
            <div v-if="viewMode === 'grid'" class="flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <ListItemIcon>
                        <CreditCard class="h-5 w-5 text-muted-foreground" />
                    </ListItemIcon>
                    <div class="flex items-center gap-2">
                        <Badge
                            v-if="!subscription.isActive"
                            variant="secondary"
                            class="text-xs"
                        >
                            Inactive
                        </Badge>
                        <ListItemActions>
                            <Button
                                v-if="subscription.url"
                                as="a"
                                :href="subscription.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                aria-label="Open subscription"
                                @click.stop
                            >
                                <ExternalLink class="h-4 w-4" />
                            </Button>

                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                aria-label="Delete subscription"
                                @click.stop="openDeleteDialog(subscription)"
                            >
                                <Trash2 class="h-4 w-4 text-muted-foreground" />
                            </Button>
                        </ListItemActions>
                    </div>
                </div>

                <p class="line-clamp-2 text-base font-medium">
                    {{ subscription.name }}
                </p>

                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <span class="font-semibold text-foreground">
                        {{
                            formatPrice(
                                subscription.price,
                                subscription.currency,
                            )
                        }}
                    </span>
                    <span
                        v-if="subscription.billingCycle"
                        class="text-sm text-muted-foreground"
                    >
                        / {{ formatBillingCycle(subscription.billingCycle) }}
                    </span>
                </div>

                <div
                    v-if="subscription.category"
                    class="flex items-center gap-2"
                >
                    <Badge variant="outline" class="text-xs">
                        {{ subscription.category }}
                    </Badge>
                    <span
                        v-if="subscription.nextBillingDate"
                        class="text-xs text-muted-foreground"
                    >
                        Next: {{ formatDate(subscription.nextBillingDate) }}
                    </span>
                </div>
            </div>

            <div v-else class="flex min-w-0 items-center gap-4 overflow-hidden">
                <ListItemIcon>
                    <CreditCard class="h-5 w-5 text-muted-foreground" />
                </ListItemIcon>

                <div class="min-w-0 flex-1">
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <p
                            class="min-w-0 flex-1 font-medium [overflow-wrap:anywhere]"
                        >
                            {{ subscription.name }}
                        </p>
                        <Badge
                            v-if="!subscription.isActive"
                            variant="secondary"
                            class="shrink-0 text-xs"
                        >
                            Inactive
                        </Badge>
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                    >
                        <span class="font-semibold text-foreground">
                            {{
                                formatPrice(
                                    subscription.price,
                                    subscription.currency,
                                )
                            }}
                        </span>
                        <span
                            v-if="subscription.billingCycle"
                            class="text-sm text-muted-foreground"
                        >
                            /
                            {{ formatBillingCycle(subscription.billingCycle) }}
                        </span>
                        <span
                            v-if="subscription.category"
                            class="hidden sm:inline"
                        >
                            &middot; {{ subscription.category }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="subscription.nextBillingDate"
                    class="hidden shrink-0 text-sm text-muted-foreground md:block"
                >
                    {{ formatDate(subscription.nextBillingDate) }}
                </div>

                <ListItemActions>
                    <Button
                        v-if="subscription.url"
                        as="a"
                        :href="subscription.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        aria-label="Open subscription"
                        @click.stop
                    >
                        <ExternalLink class="h-4 w-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        aria-label="Delete subscription"
                        @click.stop="openDeleteDialog(subscription)"
                    >
                        <Trash2 class="h-4 w-4 text-muted-foreground" />
                    </Button>
                </ListItemActions>
            </div>
        </ListItem>
    </ListContainer>

    <EmptyState
        v-else
        :title="
            searchQuery
                ? 'No subscriptions match your search.'
                : 'No subscriptions yet.'
        "
        :description="
            searchQuery
                ? 'Try a different name, category, or description.'
                : 'Track your recurring subscriptions like Netflix, Spotify, and more.'
        "
        :show-action="!searchQuery"
        action-label="Add your first subscription"
        @action="openCreateDialog"
    >
        <template #icon>
            <CreditCard class="h-12 w-12" />
        </template>
        <template #action-icon>
            <Plus class="mr-1.5 h-3.5 w-3.5" />
        </template>
    </EmptyState>
</template>
