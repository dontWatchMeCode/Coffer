<script setup lang="ts">
import InputError from '@/components/form/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';

type Props = {
    errors: Record<string, unknown>;
    idPrefix: string;
    autofocus?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    autofocus: false,
});

const title = defineModel<string>('title', { required: true });
const url = defineModel<string>('url', { required: true });
const description = defineModel<string>('description', { required: true });
const notes = defineModel<string>('notes', { required: true });

function errorFor(field: string): string | undefined {
    const error = props.errors[field];

    return typeof error === 'string' ? error : undefined;
}
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-2">
            <Label :for="`${idPrefix}-title`">Title</Label>
            <Input
                :id="`${idPrefix}-title`"
                v-model="title"
                placeholder="e.g. Laravel Documentation"
                required
                :autofocus="autofocus"
            />
            <InputError :message="errorFor('title')" />
        </div>

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-url`">URL</Label>
            <Input
                :id="`${idPrefix}-url`"
                v-model="url"
                type="url"
                placeholder="https://example.com"
                required
            />
            <InputError :message="errorFor('url')" />
        </div>

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-description`">Description</Label>
            <Input
                :id="`${idPrefix}-description`"
                v-model="description"
                placeholder="Short description of the link"
            />
            <InputError :message="errorFor('description')" />
        </div>

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-notes`">Notes</Label>
            <textarea
                :id="`${idPrefix}-notes`"
                v-model="notes"
                :class="taskInputLikeClass"
                rows="4"
                placeholder="Any additional notes about this link..."
            />
            <InputError :message="errorFor('notes')" />
        </div>
    </div>
</template>
