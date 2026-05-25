<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import ContactList from '@/components/pages/contacts/ContactList.vue';
import CreateContactDialog from '@/components/pages/contacts/CreateContactDialog.vue';
import DeleteContactDialog from '@/components/pages/contacts/DeleteContactDialog.vue';
import { Button } from '@/components/ui/button';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as contactsIndex,
    show as showContact,
    trash as contactsTrash,
} from '@/routes/team/contacts';
import type { ContactItem, PaginatedData, Team } from '@/types';

type Props = {
    contacts: PaginatedData<ContactItem>;
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    contactsIndex(currentTeamSlug.value).url,
    'contacts',
);

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

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Contacts',
                href: contactsIndex(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="Contacts" />

    <PageHeader title="Contacts" description="Manage your team address book." />

    <div class="min-w-0 flex-1 px-4 py-6">
        <div class="mx-auto w-full max-w-7xl">
            <div class="mb-4 flex items-center justify-end gap-3">
                <SearchInput
                    v-model="searchQuery"
                    data-testid="contacts-search-input"
                    placeholder="Search contacts..."
                />
            </div>

            <div class="min-w-0 space-y-4">
                <div class="flex items-center justify-end gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        title="Trash"
                        as-child
                    >
                        <Link :href="contactsTrash(currentTeamSlug).url">
                            <Trash2 class="h-4 w-4" />
                        </Link>
                    </Button>

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
                        v-if="props.contacts.data.length > 0"
                        v-model:view-mode="viewMode"
                    />
                </div>

                <InfiniteScroll data="contacts">
                    <ContactList
                        :filtered-contacts="props.contacts.data"
                        :search-query="searchQuery"
                        :navigate-to-contact="navigateToContact"
                        :open-delete-dialog="openDeleteDialog"
                        :open-create-dialog="openCreateDialog"
                        :view-mode="viewMode"
                    />
                </InfiniteScroll>
            </div>
        </div>
    </div>

    <DeleteContactDialog ref="deleteDialogRef" />
</template>
