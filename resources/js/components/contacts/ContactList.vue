<script setup lang="ts">
import {
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
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
import { Input } from '@/components/ui/input';
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

const emit = defineEmits<{
    'update:searchQuery': [value: string];
}>();
</script>

<template>
    <div class="relative mb-4">
        <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            :model-value="searchQuery"
            placeholder="Search contacts..."
            class="pl-9"
            @update:model-value="emit('update:searchQuery', String($event))"
        />
    </div>

    <div v-if="filteredContacts.length > 0" class="divide-y rounded-lg border">
        <div
            v-for="contact in filteredContacts"
            :key="contact.id"
            class="flex cursor-pointer items-start gap-4 p-4 transition-colors hover:bg-accent/50"
            @click="navigateToContact(contact)"
        >
            <ContactAvatar :name="contact.name ?? ''" size="sm" />

            <div class="min-w-0 flex-1">
                <p class="font-medium">{{ contact.name }}</p>
                <div
                    v-if="
                        contactPrimaryEmail(contact) ||
                        contactPrimaryPhone(contact)
                    "
                    class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-muted-foreground"
                >
                    <span v-if="contactPrimaryEmail(contact)" class="truncate">
                        {{ contactPrimaryEmail(contact) }}
                    </span>
                    <span
                        v-if="
                            contactPrimaryEmail(contact) &&
                            contactPrimaryPhone(contact)
                        "
                        class="hidden text-border sm:inline"
                        >|</span
                    >
                    <span v-if="contactPrimaryPhone(contact)">
                        {{ contactPrimaryPhone(contact) }}
                    </span>
                    <span
                        v-for="(extra, extraIdx) in contactSecondaryInfo(
                            contact,
                        )"
                        :key="extraIdx"
                        class="truncate"
                    >
                        {{ extra }}
                    </span>
                </div>
                <p
                    v-if="contact.address"
                    class="mt-0.5 truncate text-sm text-muted-foreground"
                >
                    {{ contact.address }}
                </p>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 cursor-pointer"
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
        class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
    >
        <UserCircle class="mb-3 h-12 w-12 text-muted-foreground/50" />
        <p class="text-sm text-muted-foreground">
            {{
                searchQuery
                    ? 'No contacts match your search.'
                    : 'No contacts yet.'
            }}
        </p>
        <Button
            v-if="!searchQuery"
            variant="outline"
            size="sm"
            class="mt-3 cursor-pointer"
            @click="openCreateDialog"
        >
            <Plus class="mr-1.5 h-3.5 w-3.5" />
            Add your first contact
        </Button>
    </div>
</template>
