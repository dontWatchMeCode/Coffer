<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ExternalLink, PowerOff } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import DetailLinkRow from '@/components/page/DetailLinkRow.vue';
import DetailSection from '@/components/page/DetailSection.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteSubscriptionDialog from '@/components/pages/subscriptions/DeleteSubscriptionDialog.vue';
import SubscriptionCategorySelect from '@/components/pages/subscriptions/SubscriptionCategorySelect.vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCopyAsMarkdown } from '@/composables/useCopyAsMarkdown';
import { serializeSubscription } from '@/lib/markdown-serializers';
import { taskInputLikeClass } from '@/lib/tasks';
import {
    index as subscriptionsIndex,
    update as updateSubscription,
} from '@/routes/team/subscriptions';
import type { ActivityHistoryConfig, SubscriptionItem, Team } from '@/types';
import type { SubscriptionCategory } from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    subscription: SubscriptionItem;
    categories?: SubscriptionCategory[];
    categoryCandidatesUrl?: string;
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

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const isEditing = ref(false);

defineOptions({
    layout: (layoutProps: {
        currentTeam?: Team | null;
        subscription?: { id: number; name: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Subscriptions',
                href: subscriptionsIndex(layoutProps.currentTeam?.slug).url,
            },
            {
                title: layoutProps.subscription?.name ?? 'Subscription',
            },
        ],
    }),
});

const editName = ref(props.subscription.name);
const editPrice = ref(props.subscription.price ?? '');
const editCurrency = ref(props.subscription.currency ?? 'USD');
const editBillingCycle = ref(props.subscription.billingCycle ?? 'monthly');
const editNextBillingDate = ref(
    props.subscription.nextBillingDate?.split('T')[0] ?? '',
);
const editUrl = ref(props.subscription.url ?? '');
const editDescription = ref(props.subscription.description ?? '');
const editNotes = ref(props.subscription.notes ?? '');
const editIsActive = ref(props.subscription.isActive);
const editCategory = ref(props.subscription.category ?? '');
const isSubmitting = ref(false);
const editFormRef = ref<HTMLFormElement | null>(null);

watch(
    () => props.subscription,
    (subscription) => {
        if (!isEditing.value) {
            resetEditFields(subscription);
        }
    },
);

function resetEditFields(subscription: SubscriptionItem): void {
    editName.value = subscription.name;
    editPrice.value = subscription.price ?? '';
    editCurrency.value = subscription.currency ?? 'USD';
    editBillingCycle.value = subscription.billingCycle ?? 'monthly';
    editNextBillingDate.value =
        subscription.nextBillingDate?.split('T')[0] ?? '';
    editUrl.value = subscription.url ?? '';
    editDescription.value = subscription.description ?? '';
    editNotes.value = subscription.notes ?? '';
    editIsActive.value = subscription.isActive;
    editCategory.value = subscription.category ?? '';
}

function cancelEdit(): void {
    resetEditFields(props.subscription);
    isEditing.value = false;
}

function submitEdit(): void {
    isSubmitting.value = true;

    router.patch(
        updateSubscription({
            current_team: currentTeamSlug.value,
            subscription: props.subscription.id,
        }).url,
        {
            name: editName.value,
            price: editPrice.value || null,
            currency: editCurrency.value || null,
            billing_cycle: editBillingCycle.value || null,
            next_billing_date: editNextBillingDate.value || null,
            url: editUrl.value || null,
            description: editDescription.value || null,
            notes: editNotes.value || null,
            is_active: editIsActive.value,
            category: editCategory.value || null,
        },
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isEditing.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

const deleteDialogRef = ref<InstanceType<
    typeof DeleteSubscriptionDialog
> | null>(null);

const { copied, copyError, copyAsMarkdown } = useCopyAsMarkdown();

function handleCopyAsMarkdown(): void {
    copyAsMarkdown(
        serializeSubscription(
            props.subscription,
            props.recordTags?.tags ?? [],
            props.recordLinks?.links ?? [],
        ),
    );
}

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
        month: 'long',
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
    <Head :title="subscription.name" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="subscription.name"
            description="Review subscription details and related records."
            :back-href="subscriptionsIndex(currentTeamSlug).url"
            back-label="Back to subscriptions"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="subscription.updatedAt"
                :on-edit="isEditing ? null : () => (isEditing = true)"
                :on-save="isEditing ? () => editFormRef?.requestSubmit() : null"
                :save-disabled="isSubmitting"
                :on-cancel="isEditing ? cancelEdit : null"
                :cancel-disabled="isSubmitting"
                :on-delete="
                    () => deleteDialogRef?.openDeleteDialog(subscription)
                "
                delete-label="Delete subscription"
                :delete-disabled="isSubmitting"
                :on-copy-as-markdown="handleCopyAsMarkdown"
                :copy-as-markdown-copied="copied"
                :copy-as-markdown-error="copyError"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #sidebar-top>
                    <ActivityHistoryPanel
                        v-if="activityHistory"
                        :config="activityHistory"
                        :team-slug="currentTeamSlug"
                    />
                </template>

                <template #main>
                    <div v-if="!isEditing" class="space-y-5">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl font-bold">
                                {{
                                    formatPrice(
                                        subscription.price,
                                        subscription.currency,
                                    )
                                }}
                            </span>
                            <span
                                v-if="subscription.billingCycle"
                                class="text-muted-foreground"
                            >
                                /
                                {{
                                    formatBillingCycle(
                                        subscription.billingCycle,
                                    )
                                }}
                            </span>
                            <Badge
                                v-if="!subscription.isActive"
                                variant="secondary"
                            >
                                <PowerOff class="mr-1 h-3 w-3" />
                                Inactive
                            </Badge>
                        </div>

                        <div
                            v-if="subscription.category"
                            class="flex items-center gap-2"
                        >
                            <Badge variant="outline">{{
                                subscription.category
                            }}</Badge>
                        </div>

                        <DetailSection
                            v-if="subscription.nextBillingDate"
                            title="Next Billing Date"
                        >
                            <p class="text-sm">
                                {{ formatDate(subscription.nextBillingDate) }}
                            </p>
                        </DetailSection>

                        <DetailSection v-if="subscription.url" title="URL">
                            <DetailLinkRow
                                :href="subscription.url"
                                :value="subscription.url"
                                external
                            >
                                <template #icon>
                                    <ExternalLink
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                </template>
                            </DetailLinkRow>
                        </DetailSection>

                        <DetailSection
                            title="Description"
                            empty="No description."
                            :has-content="Boolean(subscription.description)"
                        >
                            <p class="text-sm whitespace-pre-wrap">
                                {{ subscription.description }}
                            </p>
                        </DetailSection>

                        <DetailSection
                            title="Notes"
                            empty="No notes."
                            :has-content="Boolean(subscription.notes)"
                        >
                            <p class="text-sm whitespace-pre-wrap">
                                {{ subscription.notes }}
                            </p>
                        </DetailSection>
                    </div>

                    <form
                        v-else
                        ref="editFormRef"
                        class="space-y-5"
                        @submit.prevent="submitEdit"
                    >
                        <div class="grid gap-2">
                            <Label for="edit-subscription-name">Name</Label>
                            <Input
                                id="edit-subscription-name"
                                v-model="editName"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="grid gap-2">
                                <Label for="edit-subscription-price"
                                    >Price</Label
                                >
                                <Input
                                    id="edit-subscription-price"
                                    v-model="editPrice"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                />
                                <InputError :message="errors.price" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-subscription-currency"
                                    >Currency</Label
                                >
                                <Select v-model="editCurrency">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="USD">USD</SelectItem>
                                        <SelectItem value="EUR">EUR</SelectItem>
                                        <SelectItem value="GBP">GBP</SelectItem>
                                        <SelectItem value="CAD">CAD</SelectItem>
                                        <SelectItem value="AUD">AUD</SelectItem>
                                        <SelectItem value="JPY">JPY</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-subscription-cycle"
                                    >Billing Cycle</Label
                                >
                                <Select v-model="editBillingCycle">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="weekly"
                                            >Weekly</SelectItem
                                        >
                                        <SelectItem value="monthly"
                                            >Monthly</SelectItem
                                        >
                                        <SelectItem value="yearly"
                                            >Yearly</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="edit-subscription-next-date"
                                    >Next Billing Date</Label
                                >
                                <Input
                                    id="edit-subscription-next-date"
                                    v-model="editNextBillingDate"
                                    type="date"
                                />
                                <InputError
                                    :message="errors.next_billing_date"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label>Category</Label>
                                <SubscriptionCategorySelect
                                    v-model="editCategory"
                                    :categories="props.categories ?? []"
                                    :candidates-url="
                                        props.categoryCandidatesUrl ?? ''
                                    "
                                    placeholder="e.g. Entertainment"
                                />
                                <InputError :message="errors.category" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-subscription-url">URL</Label>
                            <Input
                                id="edit-subscription-url"
                                v-model="editUrl"
                                type="url"
                            />
                            <InputError :message="errors.url" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-subscription-description"
                                >Description</Label
                            >
                            <Input
                                id="edit-subscription-description"
                                v-model="editDescription"
                                placeholder="Short description"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-subscription-notes">Notes</Label>
                            <textarea
                                id="edit-subscription-notes"
                                v-model="editNotes"
                                :class="taskInputLikeClass"
                                rows="4"
                                placeholder="Additional notes..."
                            />
                            <InputError :message="errors.notes" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="edit-subscription-active"
                                v-model="editIsActive"
                            />
                            <Label
                                for="edit-subscription-active"
                                class="cursor-pointer text-sm font-normal"
                            >
                                Active
                            </Label>
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteSubscriptionDialog ref="deleteDialogRef" />
</template>
