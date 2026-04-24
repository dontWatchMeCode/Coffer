<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DeleteContactDialog from '@/components/contacts/DeleteContactDialog.vue';
import InputError from '@/components/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
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
import type { ContactEntry, ContactItem, Team } from '@/types';

type Props = {
    contact: ContactItem;
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

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
const editAddress = ref(props.contact.address ?? '');
const editAdditionalInfo = ref(props.contact.additionalInfo ?? '');
const isSubmitting = ref(false);
const formRef = ref<HTMLFormElement | null>(null);

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
            address: editAddress.value,
            additional_info: editAdditionalInfo.value,
        },
        {
            onSuccess: () => {
                router.visit(contactsIndex(currentTeamSlug.value).url);
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
            description="Edit contact details."
            :back-href="contactsIndex(currentTeamSlug).url"
            back-label="Back to contacts"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="contact.updatedAt"
                :on-save="() => formRef?.requestSubmit()"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(contact)"
                save-label="Save changes"
                delete-label="Delete contact"
                :save-disabled="isSubmitting"
                :delete-disabled="isSubmitting"
            >
                <template #main>
                    <form
                        ref="formRef"
                        class="space-y-4"
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
                                :message="errors['phone_numbers.0.value']"
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
                                :message="errors['email_addresses.0.value']"
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
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteContactDialog ref="deleteDialogRef" />
</template>
