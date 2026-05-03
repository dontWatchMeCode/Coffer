<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import ContactReadOnlyDetails from '@/components/pages/contacts/ContactReadOnlyDetails.vue';
import DeleteContactDialog from '@/components/pages/contacts/DeleteContactDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { emptyEntry, firstEntryValueError } from '@/lib/contacts';
import { taskInputLikeClass } from '@/lib/tasks';
import {
    index as contactsIndex,
    update as updateContact,
} from '@/routes/team/contacts';
import type { ContactEntry, ContactItem, Team } from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    contact: ContactItem;
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
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const isEditing = ref(false);

defineOptions({
    layout: (layoutProps: {
        currentTeam?: Team | null;
        contact?: { id: number; name: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Contacts',
                href: contactsIndex(layoutProps.currentTeam?.slug).url,
            },
            {
                title: layoutProps.contact?.name ?? 'Contact',
            },
        ],
    }),
});

const editName = ref(props.contact.name);
const editPhones = ref<ContactEntry[]>(
    props.contact.phoneNumbers?.length
        ? props.contact.phoneNumbers.map((e) => ({ ...e }))
        : [emptyEntry()],
);
const editEmails = ref<ContactEntry[]>(
    props.contact.emailAddresses?.length
        ? props.contact.emailAddresses.map((e) => ({ ...e }))
        : [emptyEntry()],
);
const editLinks = ref<ContactEntry[]>(
    props.contact.links?.length
        ? props.contact.links.map((e) => ({ ...e }))
        : [emptyEntry()],
);
const editAddress = ref(props.contact.address ?? '');
const editAdditionalInfo = ref(props.contact.additionalInfo ?? '');
const isSubmitting = ref(false);
const formRef = ref<HTMLFormElement | null>(null);

watch(
    () => props.contact,
    (contact) => {
        if (!isEditing.value) {
            resetEditFields(contact);
        }
    },
);

function editableEntries(
    entries: ContactEntry[] | null | undefined,
): ContactEntry[] {
    return entries?.length
        ? entries.map((entry) => ({ ...entry }))
        : [emptyEntry()];
}

function resetEditFields(contact: ContactItem): void {
    editName.value = contact.name;
    editPhones.value = editableEntries(contact.phoneNumbers);
    editEmails.value = editableEntries(contact.emailAddresses);
    editLinks.value = editableEntries(contact.links);
    editAddress.value = contact.address ?? '';
    editAdditionalInfo.value = contact.additionalInfo ?? '';
}

function cancelEdit(): void {
    resetEditFields(props.contact);
    isEditing.value = false;
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

function addEditLink(): void {
    editLinks.value.push(emptyEntry());
}

function removeEditLink(index: number): void {
    editLinks.value.splice(index, 1);
}

function submitEdit(): void {
    isSubmitting.value = true;

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
            links: editLinks.value
                .filter((e) => e.value.trim() !== '')
                .map((e) => ({ label: e.label, value: e.value })),
            address: editAddress.value,
            additional_info: editAdditionalInfo.value,
        },
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isEditing.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

const deleteDialogRef = ref<InstanceType<typeof DeleteContactDialog> | null>(
    null,
);
</script>

<template>
    <Head :title="contact.name" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="contact.name"
            description="Review contact details and related records."
            :back-href="contactsIndex(currentTeamSlug).url"
            back-label="Back to contacts"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="contact.updatedAt"
                :on-save="isEditing ? () => formRef?.requestSubmit() : null"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(contact)"
                save-label="Save changes"
                delete-label="Delete contact"
                :save-disabled="isSubmitting"
                :delete-disabled="isSubmitting"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #main>
                    <div v-if="!isEditing" class="space-y-4">
                        <ContactReadOnlyDetails :contact="contact" />

                        <div class="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="isEditing = true"
                            >
                                <Pencil class="mr-1.5 h-4 w-4" />
                                Edit
                            </Button>
                        </div>
                    </div>

                    <form
                        v-else
                        ref="formRef"
                        class="space-y-5"
                        @submit.prevent="submitEdit"
                    >
                        <div class="grid gap-2">
                            <Label for="edit-contact-name">Name</Label>
                            <Input
                                id="edit-contact-name"
                                v-model="editName"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label>Phone Numbers</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="cursor-pointer"
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
                                    <Input
                                        v-model="phone.value"
                                        type="tel"
                                        placeholder="Phone number"
                                    />
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
                                :message="
                                    firstEntryValueError(
                                        errors,
                                        'phone_numbers',
                                    )
                                "
                            />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label>Email Addresses</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="cursor-pointer"
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
                                    <Input
                                        v-model="email.value"
                                        type="email"
                                        placeholder="Email address"
                                    />
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
                                :message="
                                    firstEntryValueError(
                                        errors,
                                        'email_addresses',
                                    )
                                "
                            />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label>Links</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="cursor-pointer"
                                    @click="addEditLink"
                                >
                                    <Plus class="mr-1 h-3 w-3" />
                                    Add
                                </Button>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="(link, idx) in editLinks"
                                    :key="idx"
                                    class="flex items-center gap-2"
                                >
                                    <Input
                                        v-model="link.label"
                                        placeholder="Label"
                                        class="w-28 shrink-0"
                                    />
                                    <Input
                                        v-model="link.value"
                                        type="url"
                                        placeholder="https://example.com"
                                    />
                                    <Button
                                        v-if="editLinks.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 shrink-0 cursor-pointer text-muted-foreground hover:text-destructive"
                                        @click="removeEditLink(idx)"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                            <InputError
                                :message="firstEntryValueError(errors, 'links')"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-contact-address">Address</Label>
                            <Input
                                id="edit-contact-address"
                                v-model="editAddress"
                                placeholder="Office, city, or mailing address"
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
                                rows="4"
                                placeholder="Notes, context, or anything helpful for the team"
                            />
                            <InputError :message="errors.additional_info" />
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                type="button"
                                :disabled="isSubmitting"
                                @click="cancelEdit"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="isSubmitting">
                                Save changes
                            </Button>
                        </div>
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteContactDialog ref="deleteDialogRef" />
</template>
