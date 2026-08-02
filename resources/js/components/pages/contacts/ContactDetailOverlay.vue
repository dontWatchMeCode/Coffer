<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ActivityHistoryPanel from '@/components/activity-history/ActivityHistoryPanel.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import ContactFormFields from '@/components/pages/contacts/ContactFormFields.vue';
import ContactReadOnlyDetails from '@/components/pages/contacts/ContactReadOnlyDetails.vue';
import DeleteContactDialog from '@/components/pages/contacts/DeleteContactDialog.vue';
import { useCopyAsMarkdown } from '@/composables/useCopyAsMarkdown';
import { emptyEntry } from '@/lib/contacts';
import { serializeContact } from '@/lib/markdown-serializers';
import { update as updateContact } from '@/routes/team/contacts';
import type { ActivityHistoryConfig, ContactEntry, ContactItem } from '@/types';
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
    activityHistory?: ActivityHistoryConfig;
};

type ContactPageProps = PageProps & {
    contact?: ContactItem;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    saved: [contact: ContactItem];
}>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const isEditing = ref(false);

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
const editFormRef = ref<HTMLFormElement | null>(null);
const deleteDialogRef = ref<InstanceType<typeof DeleteContactDialog> | null>(
    null,
);

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

function close(): void {
    emit('close');
}

function cancelEdit(): void {
    resetEditFields(props.contact);
    isEditing.value = false;
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
            only: ['contact', 'recordLinks', 'recordTags', 'activityHistory'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: (response) => {
                const savedContact = (response.props as ContactPageProps)
                    .contact ?? {
                    ...props.contact,
                    name: editName.value,
                    phoneNumbers: editPhones.value
                        .filter((e) => e.value.trim() !== '')
                        .map((e) => ({ label: e.label, value: e.value })),
                    emailAddresses: editEmails.value
                        .filter((e) => e.value.trim() !== '')
                        .map((e) => ({ label: e.label, value: e.value })),
                    links: editLinks.value
                        .filter((e) => e.value.trim() !== '')
                        .map((e) => ({ label: e.label, value: e.value })),
                    address: editAddress.value,
                    additionalInfo: editAdditionalInfo.value,
                };

                emit('saved', savedContact);
                isEditing.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

const { copied, copyError, copyAsMarkdown } = useCopyAsMarkdown();

function handleCopyAsMarkdown(): void {
    copyAsMarkdown(
        serializeContact(
            props.contact,
            props.recordTags?.tags ?? [],
            props.recordLinks?.links ?? [],
        ),
    );
}
</script>

<template>
    <Head :title="contact.name" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="contact.name"
            description="Review contact details and related records."
            back-label="Back to contacts"
            :back-handler="close"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="contact.updatedAt"
                :on-edit="isEditing ? null : () => (isEditing = true)"
                :on-save="isEditing ? () => editFormRef?.requestSubmit() : null"
                :save-disabled="isSubmitting"
                :on-cancel="isEditing ? cancelEdit : null"
                :cancel-disabled="isSubmitting"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(contact)"
                delete-label="Delete contact"
                :delete-disabled="isSubmitting"
                :on-copy-as-markdown="handleCopyAsMarkdown"
                :copy-as-markdown-copied="copied"
                :copy-as-markdown-error="copyError"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #sidebar-top>
                    <ActivityHistoryPanel
                        v-if="activityHistory"
                        :config="activityHistory"
                        :team-slug="currentTeamSlug"
                    />
                </template>

                <template #main>
                    <div v-if="!isEditing">
                        <ContactReadOnlyDetails :contact="contact" />
                    </div>

                    <form
                        v-else
                        ref="editFormRef"
                        class="space-y-5"
                        @submit.prevent="submitEdit"
                    >
                        <ContactFormFields
                            v-model:name="editName"
                            v-model:phone-numbers="editPhones"
                            v-model:email-addresses="editEmails"
                            v-model:links="editLinks"
                            v-model:address="editAddress"
                            v-model:additional-info="editAdditionalInfo"
                            :errors="errors"
                            id-prefix="edit-contact"
                        />
                    </form>
                </template>
            </EditorSidebarLayout>
        </div>

        <DeleteContactDialog ref="deleteDialogRef" />
    </div>
</template>
