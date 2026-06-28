<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { ListPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SearchInput from '@/components/list/SearchInput.vue';
import ViewModeToggle from '@/components/list/ViewModeToggle.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import ContactDetailOverlay from '@/components/pages/contacts/ContactDetailOverlay.vue';
import ContactList from '@/components/pages/contacts/ContactList.vue';
import CreateContactDialog from '@/components/pages/contacts/CreateContactDialog.vue';
import DeleteContactDialog from '@/components/pages/contacts/DeleteContactDialog.vue';
import { Button } from '@/components/ui/button';
import { useListDetailOverlay } from '@/composables/useListDetailOverlay';
import { useSearch } from '@/composables/useSearch';
import { useViewMode } from '@/composables/useViewMode';
import {
    index as contactsIndex,
    show as showContact,
    trash as contactsTrash,
} from '@/routes/team/contacts';
import type {
    ActivityHistoryConfig,
    ContactItem,
    PaginatedData,
    Team,
} from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    contacts: PaginatedData<ContactItem>;
    contact?: ContactItem;
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

type ContactPageProps = PageProps & Partial<Props>;

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(
    contactsIndex(currentTeamSlug.value).url,
    'contacts',
);

const {
    closeDetail,
    rememberSavedItem,
    getPendingSavedItem,
    clearPendingSavedItem,
} = useListDetailOverlay(
    'contacts',
    currentTeamSlug.value,
    Boolean(props.contacts),
);

function navigateToContact(contact: ContactItem): void {
    router.visit(
        showContact({
            current_team: currentTeamSlug.value,
            contact: contact.id,
        }).url,
        {
            only: ['contact', 'recordLinks', 'recordTags', 'activityHistory'],
            preserveScroll: true,
        },
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

function applyPendingSavedContact(): void {
    if (props.contact || !props.contacts) {
        return;
    }

    const contact = getPendingSavedItem<ContactItem & { id: number }>();

    if (!contact || typeof contact.id !== 'number') {
        clearPendingSavedItem();

        return;
    }

    replaceLoadedContact(contact);
    clearPendingSavedItem();
}

function closeContact(): void {
    closeDetail(contactsIndex(currentTeamSlug.value).url);
}

function onSaved(contact: ContactItem): void {
    rememberSavedItem(contact);
    replaceLoadedContact(contact);
}

function replaceLoadedContact(contact: ContactItem): boolean {
    if (!props.contacts?.data.some((c) => c.id === contact.id)) {
        return false;
    }

    router.replaceProp<ContactPageProps>(
        'contacts.data',
        (contacts: unknown) => {
            if (!Array.isArray(contacts)) {
                return contacts;
            }

            return contacts.map((c) => (c.id === contact.id ? contact : c));
        },
    );

    return true;
}

watch(
    () => [props.contact?.id, props.contacts?.data],
    () => applyPendingSavedContact(),
    { immediate: true, flush: 'post' },
);

defineOptions({
    inheritAttrs: false,
    layout: (pageProps: {
        currentTeam?: Team | null;
        contact?: { id: number; name: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Contacts',
                href: contactsIndex(pageProps.currentTeam?.slug).url,
            },
            ...(pageProps.contact ? [{ title: pageProps.contact.name }] : []),
        ],
    }),
});
</script>

<template>
    <Head :title="props.contact ? props.contact.name : 'Contacts'" />

    <div v-if="props.contacts && !props.contact">
        <PageHeader
            title="Contacts"
            description="Manage your team address book."
        />

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

                    <InfiniteScroll data="contacts" :buffer="1200">
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
    </div>

    <ContactDetailOverlay
        v-if="props.contact"
        :contact="props.contact"
        :record-links="props.recordLinks"
        :record-tags="props.recordTags"
        :activity-history="props.activityHistory"
        @close="closeContact"
        @saved="onSaved"
    />

    <DeleteContactDialog ref="deleteDialogRef" />
</template>
