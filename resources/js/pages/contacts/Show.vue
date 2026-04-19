<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { MapPin, MessageSquare, Phone, Plus, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ContactAvatar from '@/components/contacts/ContactAvatar.vue';
import DeleteContactDialog from '@/components/contacts/DeleteContactDialog.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { emptyEntry } from '@/lib/contacts';
import { taskInputLikeClass } from '@/lib/tasks';
import {
    index as contactsIndex,
    update as updateContact,
} from '@/routes/team/contacts';
import type { ContactEntry, ContactItem } from '@/types';

type Props = {
    contact: ContactItem;
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const editName = ref(props.contact.name);
const editPhones = ref<ContactEntry[]>(
    props.contact.phoneNumbers?.length
        ? [...props.contact.phoneNumbers.map((e) => ({ ...e }))]
        : [emptyEntry()],
);
const editEmails = ref<ContactEntry[]>(
    props.contact.emailAddresses?.length
        ? [...props.contact.emailAddresses.map((e) => ({ ...e }))]
        : [emptyEntry()],
);
const editAddress = ref(props.contact.address ?? '');
const editAdditionalInfo = ref(props.contact.additionalInfo ?? '');

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
    router.patch(
        updateContact({
            current_team: currentTeamSlug.value,
            contact: props.contact.id,
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
            onSuccess: () => {
                router.visit(contactsIndex(currentTeamSlug.value).url);
            },
        },
    );
}

const deleteDialogRef = ref<InstanceType<typeof DeleteContactDialog> | null>(
    null,
);

const allEntries = computed(() => {
    const entries: ContactEntry[] = [];

    if (props.contact.phoneNumbers?.length) {
        for (const e of props.contact.phoneNumbers) {
            if (e.value.trim()) {
                entries.push(e);
            }
        }
    }

    if (props.contact.emailAddresses?.length) {
        for (const e of props.contact.emailAddresses) {
            if (e.value.trim()) {
                entries.push(e);
            }
        }
    }

    return entries;
});
</script>

<template>
    <Head :title="contact.name" />

    <PageHeader
        :title="contact.name"
        description="View and edit contact details."
        :back-href="contactsIndex(currentTeamSlug).url"
        back-label="Back to contacts"
    >
        <template #actions>
            <Button
                variant="destructive"
                size="sm"
                class="cursor-pointer"
                @click="deleteDialogRef?.openDeleteDialog(contact)"
            >
                <Trash2 class="mr-1.5 h-4 w-4" />
                Delete
            </Button>
        </template>
    </PageHeader>

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-3xl space-y-8">
            <div class="flex items-start gap-4">
                <ContactAvatar :name="contact.name ?? ''" size="lg" />

                <div class="min-w-0 flex-1 space-y-1">
                    <h2 class="text-lg font-semibold">{{ contact.name }}</h2>
                    <div
                        v-if="allEntries.length > 0"
                        class="flex flex-col gap-1 text-sm text-muted-foreground"
                    >
                        <div
                            v-for="(entry, entryIdx) in allEntries"
                            :key="entryIdx"
                            class="flex items-center gap-2"
                        >
                            <component
                                :is="
                                    contact.phoneNumbers?.some(
                                        (p) =>
                                            p.value === entry.value &&
                                            p.label === entry.label,
                                    )
                                        ? Phone
                                        : MessageSquare
                                "
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span
                                v-if="entry.label"
                                class="text-muted-foreground/70"
                                >{{ entry.label }}:</span
                            >
                            <span>{{ entry.value }}</span>
                        </div>
                    </div>
                    <div
                        v-if="contact.address"
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <MapPin class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ contact.address }}</span>
                    </div>
                    <p
                        v-if="contact.additionalInfo"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        {{ contact.additionalInfo }}
                    </p>
                    <p
                        v-if="contact.updatedAt"
                        class="text-xs text-muted-foreground/60"
                    >
                        Last updated
                        {{ new Date(contact.updatedAt).toLocaleDateString() }}
                    </p>
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="mb-4 text-sm font-medium text-muted-foreground">
                    Edit Contact
                </h3>

                <form class="space-y-4" @submit.prevent="submitEdit">
                    <div class="grid gap-2">
                        <Label for="edit-contact-name">Name</Label>
                        <Input
                            id="edit-contact-name"
                            v-model="editName"
                            required
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
                        <InputError
                            :message="errors['email_addresses.0.value']"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-contact-address">Address</Label>
                        <Input
                            id="edit-contact-address"
                            v-model="editAddress"
                        />
                        <InputError :message="errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-contact-additional-info">
                            Additional Info
                        </Label>
                        <textarea
                            id="edit-contact-additional-info"
                            v-model="editAdditionalInfo"
                            :class="taskInputLikeClass"
                            rows="3"
                        />
                        <InputError :message="errors.additional_info" />
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit"> Save changes </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <DeleteContactDialog ref="deleteDialogRef" />
</template>
