<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import InputError from '@/components/form/InputError.vue';
import SubscriptionCategorySelect from '@/components/pages/subscriptions/SubscriptionCategorySelect.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { taskInputLikeClass } from '@/lib/tasks';
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
const createUrl = ref('');
const createDescription = ref('');
const createNotes = ref('');
const createCategory = ref('');

function resetCreateForm(): void {
    createName.value = '';
    createPrice.value = '';
    createCurrency.value = 'USD';
    createBillingCycle.value = 'monthly';
    createNextBillingDate.value = '';
    createUrl.value = '';
    createDescription.value = '';
    createNotes.value = '';
    createCategory.value = '';
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

        <div class="grid gap-2">
            <Label for="create-subscription-name">Name</Label>
            <Input
                id="create-subscription-name"
                v-model="createName"
                placeholder="e.g. Netflix, Spotify"
                required
                autofocus
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="grid gap-2">
                <Label for="create-subscription-price">Price</Label>
                <Input
                    id="create-subscription-price"
                    v-model="createPrice"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="9.99"
                />
                <InputError :message="errors.price" />
            </div>

            <div class="grid gap-2">
                <Label for="create-subscription-currency">Currency</Label>
                <Select v-model="createCurrency">
                    <SelectTrigger>
                        <SelectValue placeholder="USD" />
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
                <Label for="create-subscription-cycle">Billing Cycle</Label>
                <Select v-model="createBillingCycle">
                    <SelectTrigger>
                        <SelectValue placeholder="Monthly" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="weekly">Weekly</SelectItem>
                        <SelectItem value="monthly">Monthly</SelectItem>
                        <SelectItem value="yearly">Yearly</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="grid gap-2">
                <Label for="create-subscription-next-date"
                    >Next Billing Date</Label
                >
                <Input
                    id="create-subscription-next-date"
                    v-model="createNextBillingDate"
                    type="date"
                />
                <InputError :message="errors.next_billing_date" />
            </div>

            <div class="grid gap-2">
                <Label for="create-subscription-category">Category</Label>
                <SubscriptionCategorySelect
                    v-model="createCategory"
                    :categories="props.categories"
                    :candidates-url="props.categoryCandidatesUrl"
                    placeholder="e.g. Entertainment"
                />
                <InputError :message="errors.category" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="create-subscription-url">URL</Label>
            <Input
                id="create-subscription-url"
                v-model="createUrl"
                type="url"
                placeholder="https://example.com"
            />
            <InputError :message="errors.url" />
        </div>

        <div class="grid gap-2">
            <Label for="create-subscription-description">Description</Label>
            <Input
                id="create-subscription-description"
                v-model="createDescription"
                placeholder="Short description"
            />
        </div>

        <div class="grid gap-2">
            <Label for="create-subscription-notes">Notes</Label>
            <textarea
                id="create-subscription-notes"
                v-model="createNotes"
                :class="taskInputLikeClass"
                rows="3"
                placeholder="Any additional notes..."
            />
        </div>
    </CreateDialog>
</template>
