<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ContactList from '@/components/contacts/ContactList.vue';
import CreateContactDialog from '@/components/contacts/CreateContactDialog.vue';
import DeleteContactDialog from '@/components/contacts/DeleteContactDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { show as showContact } from '@/routes/team/contacts';
import type { ContactEntry, ContactItem } from '@/types';

type Props = {
    contacts: ContactItem[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const searchQuery = ref('');

function searchEntries(
    entries: ContactEntry[] | null | undefined,
    q: string,
): boolean {
    if (!entries?.length) {
        return false;
    }

    return entries.some(
        (e) =>
            e.value.toLowerCase().includes(q) ||
            e.label.toLowerCase().includes(q),
    );
}

const filteredContacts = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.contacts;
    }

    const q = searchQuery.value.toLowerCase();

    return props.contacts.filter(
        (c) =>
            c.name?.toLowerCase().includes(q) ||
            searchEntries(c.emailAddresses, q) ||
            searchEntries(c.phoneNumbers, q) ||
            c.address?.toLowerCase().includes(q),
    );
});

function navigateToContact(contact: ContactItem): void {
    router.visit(
        showContact({
            current_team: currentTeamSlug.value,
            contact: contact.id,
        }),
    );
}

function contactPrimaryEmail(contact: ContactItem): string | undefined {
    return contact.emailAddresses?.find((e) => e.value.trim())?.value;
}

function contactPrimaryPhone(contact: ContactItem): string | undefined {
    return contact.phoneNumbers?.find((e) => e.value.trim())?.value;
}

function contactSecondaryInfo(contact: ContactItem): string[] {
    const emails = contact.emailAddresses?.filter((e) => e.value.trim()) ?? [];
    const phones = contact.phoneNumbers?.filter((e) => e.value.trim()) ?? [];

    return [
        ...emails.slice(1).map((e) => e.value),
        ...phones.slice(1).map((e) => e.value),
    ];
}

const createDialogRef = ref<InstanceType<typeof CreateContactDialog> | null>(
    null,
);
const deleteDialogRef = ref<InstanceType<typeof DeleteContactDialog> | null>(
    null,
);

function openCreateDialog(): void {
    if (createDialogRef.value) {
        createDialogRef.value.createDialogOpen = true;
    }
}

function openDeleteDialog(contact: ContactItem): void {
    deleteDialogRef.value?.openDeleteDialog(contact);
}
</script>

<template>
    <Head title="Contacts" />

    <PageHeader title="Contacts" description="Manage your team address book.">
        <template #actions>
            <CreateContactDialog ref="createDialogRef">
                <template #trigger>
                    <Button size="sm" class="cursor-pointer">
                        <Plus class="mr-1.5 h-4 w-4" />
                        Add Contact
                    </Button>
                </template>
            </CreateContactDialog>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-4xl">
            <ContactList
                :filtered-contacts="filteredContacts"
                :search-query="searchQuery"
                :contact-primary-email="contactPrimaryEmail"
                :contact-primary-phone="contactPrimaryPhone"
                :contact-secondary-info="contactSecondaryInfo"
                :navigate-to-contact="navigateToContact"
                :open-delete-dialog="openDeleteDialog"
                :open-create-dialog="openCreateDialog"
                @update:search-query="searchQuery = $event"
            />
        </div>
    </div>

    <DeleteContactDialog ref="deleteDialogRef" />
</template>
