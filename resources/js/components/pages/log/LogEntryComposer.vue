<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { SendHorizontal } from 'lucide-vue-next';
import { ref } from 'vue';
import LogSingleCategorySelect from '@/components/pages/log/LogSingleCategorySelect.vue';
import { Button } from '@/components/ui/button';
import { index as logIndex } from '@/routes/team/log';

type Props = {
    categories: string[];
    selectedCategories: string[];
    teamSlug: string;
};

const props = defineProps<Props>();

const body = ref('');
const category = ref('');
const isSubmitting = ref(false);

function submit(): void {
    const trimmed = body.value.trim();
    const submittedCategory =
        category.value.trim() ||
        (props.selectedCategories.length === 1
            ? props.selectedCategories[0]
            : null);

    if (!trimmed || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    body.value = '';

    router.post(
        logIndex(props.teamSlug).url,
        {
            body: trimmed,
            category: submittedCategory || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submit();
    }
}
</script>

<template>
    <section>
        <div class="mb-3">
            <h2 class="text-sm font-semibold">Quick entry</h2>
            <p class="mt-1 text-xs text-muted-foreground">
                Press Enter to log. Shift+Enter for a new line.
            </p>
        </div>

        <div class="flex max-w-4xl flex-col gap-2 sm:flex-row sm:items-end">
            <LogSingleCategorySelect
                v-model="category"
                :categories="categories"
                :disabled="isSubmitting"
                class="w-full sm:w-52"
            />

            <div class="flex items-end gap-2 sm:flex-1">
                <textarea
                    v-model="body"
                    placeholder="Log a note..."
                    class="max-h-[160px] min-h-[44px] w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    :rows="1"
                    :disabled="isSubmitting"
                    @keydown="handleKeydown"
                ></textarea>
                <Button
                    size="icon"
                    class="h-[44px] w-[44px] shrink-0 cursor-pointer"
                    :disabled="!body.trim() || isSubmitting"
                    title="Add log entry"
                    @click="submit"
                >
                    <SendHorizontal class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </section>
</template>
