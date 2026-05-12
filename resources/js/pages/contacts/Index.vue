<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ListPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import ContactList from '@/components/pages/contacts/ContactList.vue';
import CreateContactDialog from '@/components/pages/contacts/CreateContactDialog.vue';
import DeleteContactDialog from '@/components/pages/contacts/DeleteContactDialog.vue';
import { Button } from '@/components/ui/button';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as contactsIndex,
    show as showContact,
} from '@/routes/team/contacts';
import type { ContactEntry, ContactItem, Team } from '@/types';

type Props = {
    contacts: ContactItem[];
};

const props = defineProps<Props>();

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Contacts',
                href: contactsIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});

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
            searchEntries(c.links, q) ||
            c.address?.toLowerCase().includes(q),
    );
});

function navigateToContact(contact: ContactItem): void {
    router.visit(
        showContact({
            current_team: currentTeamSlug.value,
            contact: contact.id,
        }).url,
    );
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

const { viewMode } = useViewMode('contacts');
</script>

<template>
    <Head title="Contacts" />

    <PageHeader title="Contacts" description="Manage your team address book." />

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <SearchInput
                    v-model="searchQuery"
                    data-testid="contacts-search-input"
                    placeholder="Search contacts..."
                />
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-end gap-2">
                    <CreateContactDialog ref="createDialogRef">
                        <template #trigger>
                            <Button
                                size="icon"
                                title="Create contact"
                                class="cursor-pointer"
                            >
                                <ListPlus class="h-4 w-4" />
                            </Button>
                        </template>
                    </CreateContactDialog>

                    <ViewModeToggle
                        v-if="filteredContacts.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <ContactList
                    :filtered-contacts="filteredContacts"
                    :search-query="searchQuery"
                    :navigate-to-contact="navigateToContact"
                    :open-delete-dialog="openDeleteDialog"
                    :open-create-dialog="openCreateDialog"
                    :view-mode="viewMode"
                />
            </div>
        </div>
    </div>

    <DeleteContactDialog ref="deleteDialogRef" />
</template>
