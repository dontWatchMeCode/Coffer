<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/form/InputError.vue';
import SubscriptionCategorySelect from '@/components/pages/subscriptions/SubscriptionCategorySelect.vue';
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
import { billingDateLabel } from '@/lib/subscriptions';
import { taskInputLikeClass } from '@/lib/tasks';
import type { SubscriptionCategory } from '@/types';

type Props = {
    errors: Record<string, unknown>;
    idPrefix: string;
    categories?: SubscriptionCategory[];
    categoryCandidatesUrl?: string;
    autofocus?: boolean;
    showActive?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    categories: () => [],
    categoryCandidatesUrl: '',
    autofocus: false,
    showActive: false,
});

const name = defineModel<string>('name', { required: true });
const price = defineModel<string>('price', { required: true });
const currency = defineModel<string>('currency', { required: true });
const billingCycle = defineModel<string>('billingCycle', { required: true });
const firstBillingDate = defineModel<string>('firstBillingDate', {
    required: true,
});
const nextBillingDate = defineModel<string>('nextBillingDate', {
    required: true,
});
const category = defineModel<string>('category', { required: true });
const url = defineModel<string>('url', { required: true });
const description = defineModel<string>('description', { required: true });
const notes = defineModel<string>('notes', { required: true });
const isActive = defineModel<boolean>('isActive', { required: true });

const nextDateLabel = computed(() => billingDateLabel(isActive.value));

function errorFor(field: string): string | undefined {
    const error = props.errors[field];

    return typeof error === 'string' ? error : undefined;
}
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-2">
            <Label :for="`${idPrefix}-name`">Name</Label>
            <Input
                :id="`${idPrefix}-name`"
                v-model="name"
                placeholder="e.g. Netflix, Spotify"
                required
                :autofocus="autofocus"
            />
            <InputError :message="errorFor('name')" />
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="grid gap-2">
                <Label :for="`${idPrefix}-price`">Price</Label>
                <Input
                    :id="`${idPrefix}-price`"
                    v-model="price"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="9.99"
                />
                <InputError :message="errorFor('price')" />
            </div>

            <div class="grid gap-2">
                <Label>Currency</Label>
                <Select v-model="currency">
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
                <Label>Billing Cycle</Label>
                <Select v-model="billingCycle">
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
                <Label :for="`${idPrefix}-first-date`">
                    First Billing Date
                </Label>
                <Input
                    :id="`${idPrefix}-first-date`"
                    v-model="firstBillingDate"
                    type="date"
                />
                <InputError :message="errorFor('first_billing_date')" />
            </div>

            <div class="grid gap-2">
                <Label :for="`${idPrefix}-next-date`">
                    {{ nextDateLabel }}
                </Label>
                <Input
                    :id="`${idPrefix}-next-date`"
                    v-model="nextBillingDate"
                    type="date"
                />
                <InputError :message="errorFor('next_billing_date')" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label>Category</Label>
            <SubscriptionCategorySelect
                v-model="category"
                :categories="categories"
                :candidates-url="categoryCandidatesUrl"
                placeholder="e.g. Entertainment"
            />
            <InputError :message="errorFor('category')" />
        </div>

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-url`">URL</Label>
            <Input
                :id="`${idPrefix}-url`"
                v-model="url"
                type="url"
                placeholder="https://example.com"
            />
            <InputError :message="errorFor('url')" />
        </div>

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-description`">Description</Label>
            <Input
                :id="`${idPrefix}-description`"
                v-model="description"
                placeholder="Short description"
            />
            <InputError :message="errorFor('description')" />
        </div>

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-notes`">Notes</Label>
            <textarea
                :id="`${idPrefix}-notes`"
                v-model="notes"
                :class="taskInputLikeClass"
                rows="4"
                placeholder="Any additional notes..."
            />
            <InputError :message="errorFor('notes')" />
        </div>

        <div v-if="showActive" class="flex items-center gap-2">
            <Checkbox :id="`${idPrefix}-active`" v-model="isActive" />
            <Label
                :for="`${idPrefix}-active`"
                class="cursor-pointer text-sm font-normal"
            >
                Active
            </Label>
        </div>
    </div>
</template>
