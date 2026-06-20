<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    TagsInput,
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
} from '@/components/ui/tags-input';

type Props = {
    categories: string[];
    modelValue: string[];
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const open = ref(false);
const search = ref('');
const container = ref<HTMLElement | null>(null);

const filteredCategories = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.categories;
    }

    return props.categories.filter((category) =>
        category.toLowerCase().includes(query),
    );
});

function clearCategories(): void {
    emit('update:modelValue', []);
}

function toggleCategory(category: string): void {
    emit(
        'update:modelValue',
        props.modelValue.includes(category)
            ? props.modelValue.filter((value) => value !== category)
            : [...props.modelValue, category],
    );
}

function handleSearchInput(event: Event): void {
    search.value =
        event.target instanceof HTMLInputElement ? event.target.value : '';
    open.value = true;
}

function handleSelectedValues(nextValues: unknown[]): void {
    emit(
        'update:modelValue',
        nextValues
            .map((value) => String(value))
            .filter((value) => props.categories.includes(value)),
    );
}

function handleFocusOut(event: FocusEvent): void {
    const nextTarget = event.relatedTarget;

    if (nextTarget instanceof Node && container.value?.contains(nextTarget)) {
        return;
    }

    open.value = false;
}
</script>

<template>
    <div ref="container" class="relative" @focusout="handleFocusOut">
        <TagsInput
            :model-value="modelValue"
            class="min-h-8 w-full border-muted bg-background/60 shadow-none"
            @update:model-value="handleSelectedValues"
        >
            <TagsInputItem
                v-for="category in modelValue"
                :key="category"
                :value="category"
            >
                <TagsInputItemText />
                <TagsInputItemDelete />
            </TagsInputItem>
            <TagsInputInput
                :model-value="search"
                placeholder="Search categories..."
                @focus="open = true"
                @input="handleSearchInput"
                @keydown.enter.prevent
            />
        </TagsInput>

        <div
            v-if="open"
            class="absolute z-50 mt-1 w-full rounded-lg border bg-popover p-1 text-popover-foreground shadow-md"
        >
            <div class="max-h-64 overflow-y-auto">
                <button
                    type="button"
                    class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                    @mousedown.prevent="clearCategories"
                >
                    <Check
                        class="h-4 w-4"
                        :class="
                            modelValue.length === 0
                                ? 'opacity-100'
                                : 'opacity-0'
                        "
                    />
                    All entries
                </button>

                <button
                    v-for="category in filteredCategories"
                    :key="category"
                    type="button"
                    class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                    @mousedown.prevent="toggleCategory(category)"
                >
                    <Check
                        class="h-4 w-4"
                        :class="
                            modelValue.includes(category)
                                ? 'opacity-100'
                                : 'opacity-0'
                        "
                    />
                    <span class="truncate">{{ category }}</span>
                </button>

                <p
                    v-if="categories.length === 0"
                    class="px-2 py-1.5 text-xs text-muted-foreground"
                >
                    Categories appear after entries use them.
                </p>
                <p
                    v-else-if="filteredCategories.length === 0"
                    class="px-2 py-1.5 text-xs text-muted-foreground"
                >
                    No categories found.
                </p>
            </div>
        </div>
    </div>
</template>
