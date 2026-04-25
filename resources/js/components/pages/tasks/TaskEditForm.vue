<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/form/InputError.vue';
import RichTextEditor from '@/components/richtext/RichTextEditor.vue';
import { trimStoredRichText } from '@/components/richtext/storage';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    task: {
        id: number;
        title: string;
        description?: string | null;
    };
    isEditing: boolean;
    updateFormAction: Record<string, string>;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:isEditing': [value: boolean];
    editSuccess: [];
}>();

const descriptionBody = ref(props.task.description ?? '');

watch(
    () => props.task.description,
    (description) => {
        descriptionBody.value = description ?? '';
    },
);

function handleEditSuccess(): void {
    emit('editSuccess');
}
</script>

<template>
    <div v-if="!isEditing" class="space-y-4">
        <div class="rounded-lg border bg-card p-4 shadow-sm">
            <RichTextEditor
                v-if="task.description"
                :model-value="task.description"
                :editable="false"
                :on-activate="() => emit('update:isEditing', true)"
            />
            <div v-else class="text-muted-foreground italic">
                No description provided.
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button
                variant="outline"
                size="sm"
                @click="emit('update:isEditing', true)"
            >
                Edit
            </Button>
        </div>
    </div>

    <Form
        v-else
        v-bind="updateFormAction"
        class="space-y-4"
        v-slot="{ errors, processing }"
        @success="handleEditSuccess"
    >
        <input name="_return_to_edit" type="hidden" value="1" />

        <div class="rounded-lg border bg-card p-4">
            <div class="space-y-4">
                <div class="grid gap-2">
                    <Label>Title</Label>
                    <Input name="title" :default-value="task.title" required />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label class="mb-1">Description</Label>
                    <input
                        name="description"
                        type="hidden"
                        :value="trimStoredRichText(descriptionBody)"
                    />
                    <RichTextEditor
                        :model-value="descriptionBody"
                        :editable="true"
                        placeholder="Add a description..."
                        @update:model-value="(v) => (descriptionBody = v)"
                    />
                    <InputError :message="errors.description" />
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button
                variant="outline"
                type="button"
                @click="emit('update:isEditing', false)"
            >
                Cancel
            </Button>
            <Button type="submit" :disabled="processing"> Update task </Button>
        </div>
    </Form>
</template>
