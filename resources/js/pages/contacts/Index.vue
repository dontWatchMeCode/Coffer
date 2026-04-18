<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Trash2,
    UserCircle,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import {
    destroy as deleteContact,
    store as storeContact,
    update as updateContact,
} from '@/routes/team/contacts';
import type { ContactEntry, ContactItem } from '@/types';

type Props = {
    contacts: ContactItem[];
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
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

const emptyEntry = (): ContactEntry => ({ label: '', value: '' });

const createDialogOpen = ref(false);
const createName = ref('');
const createPhones = ref<ContactEntry[]>([emptyEntry()]);
const createEmails = ref<ContactEntry[]>([emptyEntry()]);
const createAddress = ref('');
const createAdditionalInfo = ref('');

function resetCreateForm(): void {
    createName.value = '';
    createPhones.value = [emptyEntry()];
    createEmails.value = [emptyEntry()];
    createAddress.value = '';
    createAdditionalInfo.value = '';
}

function handleCreateClose(value: boolean): void {
    createDialogOpen.value = value;

    if (!value) {
        resetCreateForm();
    }
}

function submitCreate(): void {
    router.post(
        storeContact(currentTeamSlug.value).url,
        {
            name: createName.value,
            phone_numbers: createPhones.value
                .filter((e) => e.value.trim() !== '')
                .map((e) => ({ label: e.label, value: e.value })),
            email_addresses: createEmails.value
                .filter((e) => e.value.trim() !== '')
                .map((e) => ({ label: e.label, value: e.value })),
            address: createAddress.value,
            additional_info: createAdditionalInfo.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                handleCreateClose(false);
            },
        },
    );
}

function addCreatePhone(): void {
    createPhones.value.push(emptyEntry());
}

function removeCreatePhone(index: number): void {
    createPhones.value.splice(index, 1);
}

function addCreateEmail(): void {
    createEmails.value.push(emptyEntry());
}

function removeCreateEmail(index: number): void {
    createEmails.value.splice(index, 1);
}

const editContact = ref<ContactItem | null>(null);
const editName = ref('');
const editPhones = ref<ContactEntry[]>([]);
const editEmails = ref<ContactEntry[]>([]);
const editAddress = ref('');
const editAdditionalInfo = ref('');
const editDialogOpen = computed({
    get: () => editContact.value !== null,
    set: (value: boolean) => {
        if (!value) {
            editContact.value = null;
        }
    },
});

function openEditDialog(contact: ContactItem): void {
    editContact.value = contact;
    editName.value = contact.name;
    editPhones.value = contact.phoneNumbers?.length
        ? [...contact.phoneNumbers.map((e) => ({ ...e }))]
        : [emptyEntry()];
    editEmails.value = contact.emailAddresses?.length
        ? [...contact.emailAddresses.map((e) => ({ ...e }))]
        : [emptyEntry()];
    editAddress.value = contact.address ?? '';
    editAdditionalInfo.value = contact.additionalInfo ?? '';
}

function addEditPhone(): void {
    editPhones.value.push(emptyEntry());
}

function removeEditPhone(index: number): void {
    editPhones.value.splice(index, 1);
}

function addEditEmail(): void {
    editEmails.value.push(emptyEntry());
}

function removeEditEmail(index: number): void {
    editEmails.value.splice(index, 1);
}

function submitEdit(): void {
    if (!editContact.value) {
        return;
    }

    router.patch(
        updateContact({
            current_team: currentTeamSlug.value,
            contact: editContact.value.id,
        }).url,
        {
            name: editName.value,
            phone_numbers: editPhones.value
                .filter((e) => e.value.trim() !== '')
                .map((e) => ({ label: e.label, value: e.value })),
            email_addresses: editEmails.value
                .filter((e) => e.value.trim() !== '')
                .map((e) => ({ label: e.label, value: e.value })),
            address: editAddress.value,
            additional_info: editAdditionalInfo.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                editContact.value = null;
            },
        },
    );
}

const deleteDialogOpen = ref(false);
const deletingContact = ref<ContactItem | null>(null);

function openDeleteDialog(contact: ContactItem): void {
    deletingContact.value = contact;
    editContact.value = null;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!deletingContact.value) {
        return;
    }

    const contact = deletingContact.value;
    deleteDialogOpen.value = false;
    deletingContact.value = null;

    router.delete(
        deleteContact({
            current_team: currentTeamSlug.value,
            contact: contact.id,
        }),
        {
            preserveScroll: true,
        },
    );
}

const contactInitials = (name: string): string => {
    return name
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const avatarColors = [
    'bg-blue-500',
    'bg-green-500',
    'bg-purple-500',
    'bg-orange-500',
    'bg-pink-500',
    'bg-teal-500',
    'bg-indigo-500',
    'bg-rose-500',
];

function avatarColor(name: string): string {
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    return avatarColors[Math.abs(hash) % avatarColors.length];
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
</script>

<template>
    <Head title="Contacts" />

    <PageHeader title="Contacts" description="Manage your team address book.">
        <template #actions>
            <Dialog :open="createDialogOpen" @update:open="handleCreateClose">
                <DialogTrigger as-child>
                    <Button size="sm" class="cursor-pointer">
                        <Plus class="mr-1.5 h-4 w-4" />
                        Add Contact
                    </Button>
                </DialogTrigger>

                <DialogContent class="max-h-[85vh] overflow-y-auto">
                    <form class="space-y-4" @submit.prevent="submitCreate">
                        <DialogHeader>
                            <DialogTitle>Add Contact</DialogTitle>
                            <DialogDescription>
                                Add a new contact to your address book.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-contact-name">Name</Label>
                            <Input
                                id="create-contact-name"
                                v-model="createName"
                                placeholder="John Doe"
                                required
                                autofocus
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label>Phone Numbers</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-6 cursor-pointer text-xs"
                                    @click="addCreatePhone"
                                >
                                    <Plus class="mr-1 h-3 w-3" />
                                    Add
                                </Button>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="(phone, idx) in createPhones"
                                    :key="idx"
                                    class="flex items-center gap-2"
                                >
                                    <Input
                                        v-model="phone.label"
                                        placeholder="Label"
                                        class="w-28 shrink-0"
                                    />
                                    <Input
                                        v-model="phone.value"
                                        type="tel"
                                        placeholder="+1 555-0123"
                                    />
                                    <Button
                                        v-if="createPhones.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 shrink-0 cursor-pointer text-muted-foreground hover:text-destructive"
                                        @click="removeCreatePhone(idx)"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                            <InputError
                                :message="errors['phone_numbers.0.value']"
                            />
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label>Email Addresses</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-6 cursor-pointer text-xs"
                                    @click="addCreateEmail"
                                >
                                    <Plus class="mr-1 h-3 w-3" />
                                    Add
                                </Button>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="(email, idx) in createEmails"
                                    :key="idx"
                                    class="flex items-center gap-2"
                                >
                                    <Input
                                        v-model="email.label"
                                        placeholder="Label"
                                        class="w-28 shrink-0"
                                    />
                                    <Input
                                        v-model="email.value"
                                        type="email"
                                        placeholder="john@example.com"
                                    />
                                    <Button
                                        v-if="createEmails.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 shrink-0 cursor-pointer text-muted-foreground hover:text-destructive"
                                        @click="removeCreateEmail(idx)"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                            <InputError
                                :message="errors['email_addresses.0.value']"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-contact-address">Address</Label>
                            <Input
                                id="create-contact-address"
                                v-model="createAddress"
                                placeholder="123 Main St, City, State"
                            />
                            <InputError :message="errors.address" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-contact-additional-info"
                                >Additional Info</Label
                            >
                            <textarea
                                id="create-contact-additional-info"
                                v-model="createAdditionalInfo"
                                :class="taskInputLikeClass"
                                rows="3"
                                placeholder="Notes, company, job title..."
                            />
                            <InputError :message="errors.additional_info" />
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit"> Add Contact </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-4xl">
            <div class="relative mb-4">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search contacts..."
                    class="pl-9"
                />
            </div>

            <div
                v-if="filteredContacts.length > 0"
                class="divide-y rounded-lg border"
            >
                <div
                    v-for="contact in filteredContacts"
                    :key="contact.id"
                    class="flex cursor-pointer items-start gap-4 p-4 transition-colors hover:bg-accent/50"
                    @click="openEditDialog(contact)"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
                        :class="avatarColor(contact.name ?? '')"
                    >
                        {{ contactInitials(contact.name ?? '?') }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ contact.name }}</p>
                        <div
                            v-if="
                                contactPrimaryEmail(contact) ||
                                contactPrimaryPhone(contact)
                            "
                            class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-muted-foreground"
                        >
                            <span
                                v-if="contactPrimaryEmail(contact)"
                                class="truncate"
                            >
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
                                v-for="(
                                    extra, extraIdx
                                ) in contactSecondaryInfo(contact)"
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
                            <DropdownMenuItem @click="openEditDialog(contact)">
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
                    @click="createDialogOpen = true"
                >
                    <Plus class="mr-1.5 h-3.5 w-3.5" />
                    Add your first contact
                </Button>
            </div>
        </div>
    </div>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Contact</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-semibold text-foreground">{{
                        deletingContact?.name
                    }}</span
                    >? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    variant="outline"
                    class="cursor-pointer"
                    @click="deleteDialogOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    class="cursor-pointer"
                    @click="confirmDelete"
                >
                    <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                    Delete
                </Button>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="editDialogOpen">
        <DialogContent v-if="editContact" class="max-h-[85vh] overflow-y-auto">
            <form class="space-y-4" @submit.prevent="submitEdit">
                <DialogHeader>
                    <DialogTitle>Edit Contact</DialogTitle>
                    <DialogDescription>
                        Update the contact details.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-contact-name">Name</Label>
                    <Input id="edit-contact-name" v-model="editName" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label>Phone Numbers</Label>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-6 cursor-pointer text-xs"
                            @click="addEditPhone"
                        >
                            <Plus class="mr-1 h-3 w-3" />
                            Add
                        </Button>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(phone, idx) in editPhones"
                            :key="idx"
                            class="flex items-center gap-2"
                        >
                            <Input
                                v-model="phone.label"
                                placeholder="Label"
                                class="w-28 shrink-0"
                            />
                            <Input v-model="phone.value" type="tel" />
                            <Button
                                v-if="editPhones.length > 1"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 shrink-0 cursor-pointer text-muted-foreground hover:text-destructive"
                                @click="removeEditPhone(idx)"
                            >
                                <X class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                    <InputError :message="errors['phone_numbers.0.value']" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label>Email Addresses</Label>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-6 cursor-pointer text-xs"
                            @click="addEditEmail"
                        >
                            <Plus class="mr-1 h-3 w-3" />
                            Add
                        </Button>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(email, idx) in editEmails"
                            :key="idx"
                            class="flex items-center gap-2"
                        >
                            <Input
                                v-model="email.label"
                                placeholder="Label"
                                class="w-28 shrink-0"
                            />
                            <Input v-model="email.value" type="email" />
                            <Button
                                v-if="editEmails.length > 1"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 shrink-0 cursor-pointer text-muted-foreground hover:text-destructive"
                                @click="removeEditEmail(idx)"
                            >
                                <X class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                    <InputError :message="errors['email_addresses.0.value']" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-contact-address">Address</Label>
                    <Input id="edit-contact-address" v-model="editAddress" />
                    <InputError :message="errors.address" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-contact-additional-info"
                        >Additional Info</Label
                    >
                    <textarea
                        id="edit-contact-additional-info"
                        v-model="editAdditionalInfo"
                        :class="taskInputLikeClass"
                        rows="3"
                    />
                    <InputError :message="errors.additional_info" />
                </div>

                <div class="flex items-center justify-between">
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="cursor-pointer"
                        @click="openDeleteDialog(editContact)"
                    >
                        <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                        Delete
                    </Button>

                    <Button type="submit"> Save changes </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
