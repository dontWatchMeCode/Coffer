<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/form/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { emptyEntry } from '@/lib/contacts';
import { taskInputLikeClass } from '@/lib/tasks';
import { store as storeContact } from '@/routes/team/contacts';
import type { ContactEntry } from '@/types';

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

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

defineExpose({
    createDialogOpen,
    handleCreateClose,
});
</script>

<template>
    <Dialog :open="createDialogOpen" @update:open="handleCreateClose">
        <DialogTrigger as-child>
            <slot name="trigger" />
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
                    <InputError :message="errors['email_addresses.0.value']" />
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
                    <Label for="create-contact-additional-info">
                        Additional Info
                    </Label>
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
