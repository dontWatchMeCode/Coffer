<script setup lang="ts">
import { Pencil, Plus, Trash2, UserCircle } from 'lucide-vue-next';
import { computed } from 'vue';
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

        return {
            contact,
            primaryEmail: emails[0]?.value,
            primaryPhone: phones[0]?.value,
            secondaryInfo: [
                ...emails.slice(1).map((e) => e.value),
                ...phones.slice(1).map((e) => e.value),
            ],
        };
    }),
);
</script>

<template>
    <div v-if="contactsWithDisplay.length > 0" class="space-y-3">
        <div
            v-for="item in contactsWithDisplay"
            :key="item.contact.id"
            class="group flex cursor-pointer items-center gap-4 rounded-lg border bg-card p-3 transition-colors hover:bg-accent/50 dark:bg-card/50"
            @click="navigateToContact(item.contact)"
        >
            <ContactAvatar :name="item.contact.name ?? ''" size="sm" />

            <div class="min-w-0 flex-1">
                <p class="truncate font-medium">{{ item.contact.name }}</p>
                <p
                    v-if="item.primaryEmail || item.primaryPhone"
                    class="truncate text-sm text-muted-foreground"
                >
                    {{
                        [
                            item.primaryEmail,
                            item.primaryPhone,
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

            <div class="flex shrink-0 items-center gap-1">
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
            </div>
        </div>
    </div>

    <div
        v-else
        class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center"
    >
        <UserCircle class="mb-3 h-12 w-12 text-muted-foreground/50" />
        <p class="font-medium">
            {{
                searchQuery
                    ? 'No contacts match your search.'
                    : 'No contacts yet.'
            }}
        </p>
        <p class="mt-1 max-w-sm text-sm text-muted-foreground">
            {{
                searchQuery
                    ? 'Try a different name, email, phone number, or address.'
                    : 'Create your first team contact to start building a shared address book.'
            }}
        </p>
        <Button
            v-if="!searchQuery"
            variant="outline"
            size="sm"
            class="mt-4 cursor-pointer"
            @click="openCreateDialog"
        >
            <Plus class="mr-1.5 h-3.5 w-3.5" />
            Add your first contact
        </Button>
    </div>
</template>
