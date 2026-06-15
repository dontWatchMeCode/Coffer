<script setup lang="ts">
import { Plus, Trash2, UserCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ContactAvatar from '@/components/pages/contacts/ContactAvatar.vue';
import { Button } from '@/components/ui/button';
import type { ViewMode } from '@/composables/useViewMode';
import type { ContactItem } from '@/types';

type Props = {
    filteredContacts: ContactItem[];
    searchQuery: string;
    navigateToContact: (contact: ContactItem) => void;
    openDeleteDialog: (contact: ContactItem) => void;
    openCreateDialog: () => void;
    viewMode: ViewMode;
};

const props = defineProps<Props>();

function filterEntries<T extends { value: string }>(
    entries: T[] | null | undefined,
): T[] {
    return entries?.filter((e) => e.value.trim()) ?? [];
}

function buildContactInfo(item: {
    primaryEmail?: string;
    primaryPhone?: string;
    primaryLink?: string;
    secondaryInfo: string[];
}): string {
    return [
        item.primaryEmail,
        item.primaryPhone,
        item.primaryLink,
        ...item.secondaryInfo,
    ]
        .filter(Boolean)
        .join(' • ');
}

const contactsWithDisplay = computed(() =>
    props.filteredContacts.map((contact) => {
        const emails = filterEntries(contact.emailAddresses);
        const phones = filterEntries(contact.phoneNumbers);
        const links = filterEntries(contact.links);

        return {
            contact,
            primaryEmail: emails[0]?.value,
            primaryPhone: phones[0]?.value,
            primaryLink: links[0]?.value,
            secondaryInfo: [
                ...emails.slice(1).map((e) => e.value),
                ...phones.slice(1).map((e) => e.value),
                ...links.slice(1).map((e) => e.value),
            ],
        };
    }),
);
</script>

<template>
    <ListContainer v-if="contactsWithDisplay.length > 0" :layout="viewMode">
        <ListItem
            v-for="item in contactsWithDisplay"
            :key="item.contact.id"
            @click="navigateToContact(item.contact)"
        >
            <div v-if="viewMode === 'grid'" class="flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <ContactAvatar :name="item.contact.name ?? ''" size="sm" />
                    <ListItemActions>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8"
                            aria-label="Delete contact"
                            @click.stop="openDeleteDialog(item.contact)"
                        >
                            <Trash2 class="h-4 w-4 text-muted-foreground" />
                        </Button>
                    </ListItemActions>
                </div>

                <p class="line-clamp-2 text-base font-medium">
                    {{ item.contact.name }}
                </p>

                <p
                    v-if="
                        item.primaryEmail ||
                        item.primaryPhone ||
                        item.primaryLink
                    "
                    class="line-clamp-4 text-sm text-muted-foreground"
                >
                    {{ buildContactInfo(item) }}
                </p>
                <p
                    v-else-if="item.contact.address"
                    class="line-clamp-4 text-sm text-muted-foreground"
                >
                    {{ item.contact.address }}
                </p>
                <p v-else class="text-sm text-muted-foreground italic">
                    No contact info yet.
                </p>
            </div>

            <div v-else class="flex min-w-0 items-center gap-4 overflow-hidden">
                <ContactAvatar :name="item.contact.name ?? ''" size="sm" />

                <div class="min-w-0 flex-1">
                    <p class="font-medium [overflow-wrap:anywhere]">
                        {{ item.contact.name }}
                    </p>
                    <p
                        v-if="
                            item.primaryEmail ||
                            item.primaryPhone ||
                            item.primaryLink
                        "
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground"
                    >
                        {{ buildContactInfo(item) }}
                    </p>
                    <p
                        v-else-if="item.contact.address"
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground"
                    >
                        {{ item.contact.address }}
                    </p>
                    <p
                        v-else
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground italic"
                    >
                        No contact info yet.
                    </p>
                </div>

                <ListItemActions>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        aria-label="Delete contact"
                        @click.stop="openDeleteDialog(item.contact)"
                    >
                        <Trash2 class="h-4 w-4 text-muted-foreground" />
                    </Button>
                </ListItemActions>
            </div>
        </ListItem>
    </ListContainer>

    <EmptyState
        v-else
        :title="
            searchQuery ? 'No contacts match your search.' : 'No contacts yet.'
        "
        :description="
            searchQuery
                ? 'Try a different name, email, phone number, link, or address.'
                : 'Create your first team contact to start building a shared address book.'
        "
        :show-action="!searchQuery"
        action-label="Add your first contact"
        @action="openCreateDialog"
    >
        <template #icon>
            <UserCircle class="h-12 w-12" />
        </template>
        <template #action-icon>
            <Plus class="mr-1.5 h-3.5 w-3.5" />
        </template>
    </EmptyState>
</template>
