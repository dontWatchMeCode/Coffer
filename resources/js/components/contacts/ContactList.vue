<script setup lang="ts">
import {
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
    UserCircle,
} from 'lucide-vue-next';
import ContactAvatar from '@/components/contacts/ContactAvatar.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ContactItem } from '@/types';

type Props = {
    filteredContacts: ContactItem[];
    searchQuery: string;
    contactPrimaryEmail: (contact: ContactItem) => string | undefined;
    contactPrimaryPhone: (contact: ContactItem) => string | undefined;
    contactSecondaryInfo: (contact: ContactItem) => string[];
    navigateToContact: (contact: ContactItem) => void;
    openDeleteDialog: (contact: ContactItem) => void;
    openCreateDialog: () => void;
};

defineProps<Props>();
</script>

<template>
    <div v-if="filteredContacts.length > 0" class="space-y-3">
        <div
            v-for="contact in filteredContacts"
            :key="contact.id"
            class="group flex cursor-pointer items-center gap-4 rounded-lg border bg-card p-3 transition-colors hover:bg-accent/50 dark:bg-card/50"
            @click="navigateToContact(contact)"
        >
            <ContactAvatar :name="contact.name ?? ''" size="sm" />

            <div class="min-w-0 flex-1">
                <p class="truncate font-medium">{{ contact.name }}</p>
                <p
                    v-if="
                        contactPrimaryEmail(contact) ||
                        contactPrimaryPhone(contact)
                    "
                    class="truncate text-sm text-muted-foreground"
                >
                    {{
                        [
                            contactPrimaryEmail(contact),
                            contactPrimaryPhone(contact),
                            ...contactSecondaryInfo(contact),
                        ]
                            .filter(Boolean)
                            .join(' • ')
                    }}
                </p>
                <p
                    v-else-if="contact.address"
                    class="truncate text-sm text-muted-foreground"
                >
                    {{ contact.address }}
                </p>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 cursor-pointer opacity-70 transition-opacity group-hover:opacity-100"
                        @click.stop
                    >
                        <MoreHorizontal class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="navigateToContact(contact)">
                        <Pencil class="mr-2 h-4 w-4" />
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="text-destructive focus:text-destructive"
                        @click="openDeleteDialog(contact)"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
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
