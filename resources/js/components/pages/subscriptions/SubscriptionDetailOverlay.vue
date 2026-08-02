<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ExternalLink, PowerOff } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import DetailLinkRow from '@/components/page/DetailLinkRow.vue';
import DetailSection from '@/components/page/DetailSection.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteSubscriptionDialog from '@/components/pages/subscriptions/DeleteSubscriptionDialog.vue';
import SubscriptionFormFields from '@/components/pages/subscriptions/SubscriptionFormFields.vue';
import { Badge } from '@/components/ui/badge';
import { useCopyAsMarkdown } from '@/composables/useCopyAsMarkdown';
import { serializeSubscription } from '@/lib/markdown-serializers';
import { billingDateLabel } from '@/lib/subscriptions';
import { update as updateSubscription } from '@/routes/team/subscriptions';
import type { ActivityHistoryConfig, SubscriptionItem } from '@/types';
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

type SubscriptionPageProps = PageProps & {
    subscription?: SubscriptionItem;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    saved: [subscription: SubscriptionItem];
}>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const isEditing = ref(false);

const editName = ref(props.subscription.name);
const editPrice = ref(props.subscription.price ?? '');
const editCurrency = ref(props.subscription.currency ?? 'USD');
const editBillingCycle = ref(props.subscription.billingCycle ?? 'monthly');
const editNextBillingDate = ref(
    props.subscription.nextBillingDate?.split('T')[0] ?? '',
);
const editFirstBillingDate = ref(
    props.subscription.firstBillingDate?.split('T')[0] ?? '',
);
const editUrl = ref(props.subscription.url ?? '');
const editDescription = ref(props.subscription.description ?? '');
const editNotes = ref(props.subscription.notes ?? '');
const editIsActive = ref(props.subscription.isActive);
const editCategory = ref(props.subscription.category ?? '');
const isSubmitting = ref(false);
const editFormRef = ref<HTMLFormElement | null>(null);
const deleteDialogRef = ref<InstanceType<
    typeof DeleteSubscriptionDialog
> | null>(null);
const displayBillingDateLabel = computed(() =>
    billingDateLabel(props.subscription.isActive),
);

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
    editFirstBillingDate.value =
        subscription.firstBillingDate?.split('T')[0] ?? '';
    editUrl.value = subscription.url ?? '';
    editDescription.value = subscription.description ?? '';
    editNotes.value = subscription.notes ?? '';
    editIsActive.value = subscription.isActive;
    editCategory.value = subscription.category ?? '';
}

function close(): void {
    emit('close');
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
            first_billing_date: editFirstBillingDate.value || null,
            url: editUrl.value || null,
            description: editDescription.value || null,
            notes: editNotes.value || null,
            is_active: editIsActive.value,
            category: editCategory.value || null,
        },
        {
            only: [
                'subscription',
                'recordLinks',
                'recordTags',
                'activityHistory',
            ],
            preserveScroll: true,
            preserveState: true,
            onSuccess: (response) => {
                const savedSubscription = (
                    response.props as SubscriptionPageProps
                ).subscription ?? {
                    ...props.subscription,
                    name: editName.value,
                    price: editPrice.value || null,
                    currency: editCurrency.value || null,
                    billingCycle: editBillingCycle.value || null,
                    nextBillingDate: editNextBillingDate.value || null,
                    firstBillingDate: editFirstBillingDate.value || null,
                    url: editUrl.value || null,
                    description: editDescription.value || null,
                    notes: editNotes.value || null,
                    isActive: editIsActive.value,
                    category: editCategory.value || null,
                };

                emit('saved', savedSubscription);
                isEditing.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

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
            back-label="Back to subscriptions"
            :back-handler="close"
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
                            v-if="subscription.firstBillingDate"
                            title="First Billing Date"
                        >
                            <p class="text-sm">
                                {{ formatDate(subscription.firstBillingDate) }}
                            </p>
                        </DetailSection>

                        <DetailSection
                            v-if="subscription.nextBillingDate"
                            :title="displayBillingDateLabel"
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
                        <SubscriptionFormFields
                            v-model:name="editName"
                            v-model:price="editPrice"
                            v-model:currency="editCurrency"
                            v-model:billing-cycle="editBillingCycle"
                            v-model:first-billing-date="editFirstBillingDate"
                            v-model:next-billing-date="editNextBillingDate"
                            v-model:category="editCategory"
                            v-model:url="editUrl"
                            v-model:description="editDescription"
                            v-model:notes="editNotes"
                            v-model:is-active="editIsActive"
                            :errors="errors"
                            :categories="props.categories ?? []"
                            :category-candidates-url="
                                props.categoryCandidatesUrl ?? ''
                            "
                            id-prefix="edit-subscription"
                            show-active
                        />
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>

        <DeleteSubscriptionDialog ref="deleteDialogRef" />
    </div>
</template>
