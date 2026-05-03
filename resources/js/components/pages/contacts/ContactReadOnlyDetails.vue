<script setup lang="ts">
import { LinkIcon, Mail, MapPin, Phone } from 'lucide-vue-next';
import DetailLinkRow from '@/components/page/DetailLinkRow.vue';
import DetailSection from '@/components/page/DetailSection.vue';
import type { ContactEntry, ContactItem } from '@/types';

type Props = {
    contact: ContactItem;
};

defineProps<Props>();

function hasEntries(entries: ContactEntry[] | null | undefined): boolean {
    return Boolean(entries?.length);
}

function entryLabel(entry: ContactEntry): string {
    return entry.label || 'Other';
}
</script>

<template>
    <div class="space-y-5">
        <DetailSection
            title="Phone Numbers"
            empty="No phone numbers."
            :has-content="hasEntries(contact.phoneNumbers)"
        >
            <div class="space-y-2">
                <DetailLinkRow
                    v-for="phone in contact.phoneNumbers"
                    :key="`${phone.label}-${phone.value}`"
                    :href="`tel:${phone.value}`"
                    :value="phone.value"
                    :label="entryLabel(phone)"
                >
                    <template #icon>
                        <Phone class="h-4 w-4 shrink-0 text-muted-foreground" />
                    </template>
                </DetailLinkRow>
            </div>
        </DetailSection>

        <DetailSection
            title="Email Addresses"
            empty="No email addresses."
            :has-content="hasEntries(contact.emailAddresses)"
        >
            <div class="space-y-2">
                <DetailLinkRow
                    v-for="email in contact.emailAddresses"
                    :key="`${email.label}-${email.value}`"
                    :href="`mailto:${email.value}`"
                    :value="email.value"
                    :label="entryLabel(email)"
                >
                    <template #icon>
                        <Mail class="h-4 w-4 shrink-0 text-muted-foreground" />
                    </template>
                </DetailLinkRow>
            </div>
        </DetailSection>

        <DetailSection
            title="Links"
            empty="No links."
            :has-content="hasEntries(contact.links)"
        >
            <div class="space-y-2">
                <DetailLinkRow
                    v-for="link in contact.links"
                    :key="`${link.label}-${link.value}`"
                    :href="link.value"
                    :value="link.value"
                    :label="entryLabel(link)"
                    external
                >
                    <template #icon>
                        <LinkIcon
                            class="h-4 w-4 shrink-0 text-muted-foreground"
                        />
                    </template>
                </DetailLinkRow>
            </div>
        </DetailSection>

        <DetailSection
            title="Address"
            empty="No address."
            :has-content="Boolean(contact.address)"
        >
            <div class="flex gap-2 text-sm">
                <MapPin class="h-4 w-4 shrink-0 text-muted-foreground" />
                <p class="whitespace-pre-wrap">{{ contact.address }}</p>
            </div>
        </DetailSection>

        <DetailSection
            title="Additional Info"
            empty="No additional info."
            :has-content="Boolean(contact.additionalInfo)"
        >
            <p class="text-sm whitespace-pre-wrap">
                {{ contact.additionalInfo }}
            </p>
        </DetailSection>
    </div>
</template>
