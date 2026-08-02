<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreateDialog from '@/components/dialogs/CreateDialog.vue';
import ContactFormFields from '@/components/pages/contacts/ContactFormFields.vue';
import { emptyEntry } from '@/lib/contacts';
import { store as storeContact } from '@/routes/team/contacts';
import type { ContactEntry } from '@/types';

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const createDialogOpen = ref(false);
const createName = ref('');
const createPhones = ref<ContactEntry[]>([emptyEntry()]);
const createEmails = ref<ContactEntry[]>([emptyEntry()]);
const createLinks = ref<ContactEntry[]>([emptyEntry()]);
const createAddress = ref('');
const createAdditionalInfo = ref('');

function resetCreateForm(): void {
    createName.value = '';
    createPhones.value = [emptyEntry()];
    createEmails.value = [emptyEntry()];
    createLinks.value = [emptyEntry()];
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
            links: createLinks.value
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

defineExpose({
    createDialogOpen,
    handleCreateClose,
});
</script>

<template>
    <CreateDialog
        :open="createDialogOpen"
        title="Add Contact"
        description="Add a new contact to your address book."
        submit-label="Add Contact"
        @update:open="handleCreateClose"
        @submit="submitCreate"
    >
        <template #trigger>
            <slot name="trigger" />
        </template>

        <ContactFormFields
            v-model:name="createName"
            v-model:phone-numbers="createPhones"
            v-model:email-addresses="createEmails"
            v-model:links="createLinks"
            v-model:address="createAddress"
            v-model:additional-info="createAdditionalInfo"
            :errors="errors"
            id-prefix="create-contact"
            autofocus
        />
    </CreateDialog>
</template>
