<script setup lang="ts">
import InputError from '@/components/form/InputError.vue';
import ContactEntryFields from '@/components/pages/contacts/ContactEntryFields.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { firstEntryValueError } from '@/lib/contacts';
import { taskInputLikeClass } from '@/lib/tasks';
import type { ContactEntry } from '@/types';

type Props = {
    errors: Record<string, unknown>;
    idPrefix: string;
    autofocus?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    autofocus: false,
});

const name = defineModel<string>('name', { required: true });
const phoneNumbers = defineModel<ContactEntry[]>('phoneNumbers', {
    required: true,
});
const emailAddresses = defineModel<ContactEntry[]>('emailAddresses', {
    required: true,
});
const links = defineModel<ContactEntry[]>('links', { required: true });
const address = defineModel<string>('address', { required: true });
const additionalInfo = defineModel<string>('additionalInfo', {
    required: true,
});

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
                placeholder="John Doe"
                required
                :autofocus="autofocus"
            />
            <InputError :message="errorFor('name')" />
        </div>

        <ContactEntryFields
            v-model="phoneNumbers"
            label="Phone Numbers"
            input-type="tel"
            placeholder="+1 555-0123"
            :error="firstEntryValueError(errors, 'phone_numbers')"
        />

        <ContactEntryFields
            v-model="emailAddresses"
            label="Email Addresses"
            input-type="email"
            placeholder="john@example.com"
            :error="firstEntryValueError(errors, 'email_addresses')"
        />

        <ContactEntryFields
            v-model="links"
            label="Links"
            input-type="url"
            placeholder="https://example.com"
            :error="firstEntryValueError(errors, 'links')"
        />

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-address`">Address</Label>
            <Input
                :id="`${idPrefix}-address`"
                v-model="address"
                placeholder="123 Main St, City, State"
            />
            <InputError :message="errorFor('address')" />
        </div>

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-additional-info`">
                Additional Info
            </Label>
            <textarea
                :id="`${idPrefix}-additional-info`"
                v-model="additionalInfo"
                :class="taskInputLikeClass"
                rows="4"
                placeholder="Notes, company, job title..."
            />
            <InputError :message="errorFor('additional_info')" />
        </div>
    </div>
</template>
