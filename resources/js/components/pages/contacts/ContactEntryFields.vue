<script setup lang="ts">
import { Plus, X } from 'lucide-vue-next';
import InputError from '@/components/form/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { emptyEntry } from '@/lib/contacts';
import type { ContactEntry } from '@/types';

type Props = {
    label: string;
    inputType: 'email' | 'tel' | 'url';
    placeholder: string;
    error?: string;
};

defineProps<Props>();

const entries = defineModel<ContactEntry[]>({ required: true });

function addEntry(): void {
    entries.value.push(emptyEntry());
}

function removeEntry(index: number): void {
    entries.value.splice(index, 1);
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <Label>{{ label }}</Label>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="h-6 cursor-pointer text-xs"
                @click="addEntry"
            >
                <Plus class="mr-1 h-3 w-3" />
                Add
            </Button>
        </div>

        <div class="space-y-2">
            <div
                v-for="(entry, index) in entries"
                :key="index"
                class="flex items-center gap-2"
            >
                <Input
                    v-model="entry.label"
                    placeholder="Label"
                    class="w-28 shrink-0"
                />
                <Input
                    v-model="entry.value"
                    :type="inputType"
                    :placeholder="placeholder"
                />
                <Button
                    v-if="entries.length > 1"
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 shrink-0 cursor-pointer text-muted-foreground hover:text-destructive"
                    @click="removeEntry(index)"
                >
                    <X class="h-3.5 w-3.5" />
                </Button>
            </div>
        </div>

        <InputError :message="error" />
    </div>
</template>
