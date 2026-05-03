<script setup lang="ts">
import { Pencil, Plus, Trash2, UserCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ContactAvatar from '@/components/pages/contacts/ContactAvatar.vue';
import { Button } from '@/components/ui/button';
import type { ContactItem } from '@/types';

type Props = {
    filteredContacts: ContactItem[];
    searchQuery: string;
    navigateToContact: (contact: ContactItem) => void;
    openDeleteDialog: (contact: ContactItem) => void;
    openCreateDialog: () => void;
};

const props = defineProps<Props>();

const contactsWithDisplay = computed(() =>
    props.filteredContacts.map((contact) => {
        const emails =
            contact.emailAddresses?.filter((e) => e.value.trim()) ?? [];
        const phones =
            contact.phoneNumbers?.filter((e) => e.value.trim()) ?? [];
        const links = contact.links?.filter((e) => e.value.trim()) ?? [];

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
    <ListContainer v-if="contactsWithDisplay.length > 0">
        <ListItem
            v-for="item in contactsWithDisplay"
            :key="item.contact.id"
            @click="navigateToContact(item.contact)"
        >
            <div class="flex items-center gap-4">
                <ContactAvatar :name="item.contact.name ?? ''" size="sm" />

                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ item.contact.name }}</p>
                    <p
                        v-if="
                            item.primaryEmail ||
                            item.primaryPhone ||
                            item.primaryLink
                        "
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{
                            [
                                item.primaryEmail,
                                item.primaryPhone,
                                item.primaryLink,
                                ...item.secondaryInfo,
                            ]
                                .filter(Boolean)
                                .join(' • ')
                        }}
                    </p>
                    <p
                        v-else-if="item.contact.address"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ item.contact.address }}
                    </p>
                </div>

                <ListItemActions>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        aria-label="Edit contact"
                        @click.stop="navigateToContact(item.contact)"
                    >
                        <Pencil class="h-4 w-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                        aria-label="Delete contact"
                        @click.stop="openDeleteDialog(item.contact)"
                    >
                        <Trash2 class="h-4 w-4" />
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
