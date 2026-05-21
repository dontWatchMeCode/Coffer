<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TrashPage from '@/components/pages/trash/TrashPage.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';
import { useSearch } from '@/composables/useSearch';
import { forceDestroy, index, restore, trash } from '@/routes/team/contacts';
import type { ContactItem, PaginatedData, Team } from '@/types';

const props = defineProps<{
    contacts: PaginatedData<ContactItem>;
}>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const { searchQuery } = useSearch(trash(currentTeamSlug.value).url, 'contacts');

const records = computed<TrashRecord[]>(() =>
    props.contacts.data.map((contact) => ({
        id: contact.id,
        title: contact.name,
        subtitle:
            contact.emailAddresses?.[0]?.value ??
            contact.phoneNumbers?.[0]?.value,
        deletedAt: contact.deletedAt,
    })),
);

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            { title: 'Contacts', href: index(pageProps.currentTeam?.slug).url },
            { title: 'Trash', href: trash(pageProps.currentTeam?.slug).url },
        ],
    }),
});
</script>

<template>
    <TrashPage
        v-model:search-query="searchQuery"
        title="Deleted Contacts"
        description="Restore contacts or delete them permanently."
        module-name="Contacts"
        scroll-data="contacts"
        :records="records"
        :back-href="index(currentTeamSlug).url"
        back-label="Back to contacts"
        :restore-url="
            (record) =>
                restore({ current_team: currentTeamSlug, contact: record.id })
                    .url
        "
        :force-delete-url="
            (record) =>
                forceDestroy({
                    current_team: currentTeamSlug,
                    contact: record.id,
                }).url
        "
    />
</template>
