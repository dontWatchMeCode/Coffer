<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import SubscriptionFormFields from '@/components/pages/subscriptions/SubscriptionFormFields.vue';
import { store as storeSubscription } from '@/routes/team/subscriptions';
import type { SubscriptionCategory } from '@/types';

type Props = {
    categories?: SubscriptionCategory[];
    categoryCandidatesUrl?: string;
};

const props = withDefaults(defineProps<Props>(), {
    categories: () => [],
    categoryCandidatesUrl: '',
});

const page = usePage<PageProps>();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const createDialogOpen = ref(false);
const createName = ref('');
const createPrice = ref('');
const createCurrency = ref('USD');
const createBillingCycle = ref('monthly');
const createNextBillingDate = ref('');
const createFirstBillingDate = ref('');
const createUrl = ref('');
const createDescription = ref('');
const createNotes = ref('');
const createCategory = ref('');
const createIsActive = ref(true);

function resetCreateForm(): void {
    createName.value = '';
    createPrice.value = '';
    createCurrency.value = 'USD';
    createBillingCycle.value = 'monthly';
    createNextBillingDate.value = '';
    createFirstBillingDate.value = '';
    createUrl.value = '';
    createDescription.value = '';
    createNotes.value = '';
    createCategory.value = '';
    createIsActive.value = true;
}

function handleCreateClose(value: boolean): void {
    createDialogOpen.value = value;

    if (!value) {
        resetCreateForm();
    }
}

function submitCreate(): void {
    router.post(
        storeSubscription(currentTeamSlug.value).url,
        {
            name: createName.value,
            price: createPrice.value || null,
            currency: createCurrency.value || null,
            billing_cycle: createBillingCycle.value || null,
            next_billing_date: createNextBillingDate.value || null,
            first_billing_date: createFirstBillingDate.value || null,
            url: createUrl.value || null,
            description: createDescription.value || null,
            notes: createNotes.value || null,
            category: createCategory.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                handleCreateClose(false);
            },
        },
    );
}

defineExpose({
    createDialogOpen,
    handleCreateClose,
});
</script>

<template>
    <CreateDialog
        :open="createDialogOpen"
        title="Add Subscription"
        description="Track a new recurring subscription for your team."
        submit-label="Add Subscription"
        @update:open="handleCreateClose"
        @submit="submitCreate"
    >
        <template #trigger>
            <slot name="trigger" />
        </template>

        <SubscriptionFormFields
            v-model:name="createName"
            v-model:price="createPrice"
            v-model:currency="createCurrency"
            v-model:billing-cycle="createBillingCycle"
            v-model:first-billing-date="createFirstBillingDate"
            v-model:next-billing-date="createNextBillingDate"
            v-model:category="createCategory"
            v-model:url="createUrl"
            v-model:description="createDescription"
            v-model:notes="createNotes"
            v-model:is-active="createIsActive"
            :errors="errors"
            :categories="props.categories"
            :category-candidates-url="props.categoryCandidatesUrl"
            id-prefix="create-subscription"
            autofocus
        />
    </CreateDialog>
</template>
