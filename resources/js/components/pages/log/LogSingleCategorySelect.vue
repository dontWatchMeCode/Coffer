<script setup lang="ts">
import { CheckIcon, Plus } from 'lucide-vue-next';
import {
    ListboxContent,
    ListboxFilter,
    ListboxItem,
    ListboxItemIndicator,
    ListboxRoot,
} from 'reka-ui';
import type { AcceptableInputValue, AcceptableValue } from 'reka-ui';
import { computed, nextTick, ref } from 'vue';
import {
    TagsInput,
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
} from '@/components/ui/tags-input';

type Props = {
    categories: string[];
    modelValue: string;
    disabled?: boolean;
    placeholder?: string;
};

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    placeholder: 'Category',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const search = ref('');
const dropdownOpen = ref(false);
const picker = ref<HTMLElement | null>(null);

const selectedValues = computed(() =>
    props.modelValue ? [props.modelValue] : [],
);

const trimmedSearch = computed(() => search.value.trim());

const matchingCategories = computed(() => {
    const query = trimmedSearch.value.toLowerCase();

    if (!query) {
        return props.categories;
    }

    return props.categories.filter((category) =>
        category.toLowerCase().includes(query),
    );
});

const hasExactCategory = computed(() =>
    props.categories.some(
        (category) =>
            category.toLowerCase() === trimmedSearch.value.toLowerCase(),
    ),
);

const canCreate = computed(
    () => trimmedSearch.value !== '' && !hasExactCategory.value,
);

function setCategory(value: string): void {
    emit('update:modelValue', value);
    search.value = '';
    dropdownOpen.value = false;
}

function reopenPicker(event: MouseEvent): void {
    event.stopPropagation();
    emit('update:modelValue', '');
    dropdownOpen.value = true;

    nextTick(() => {
        const input = picker.value?.querySelector('input');
        input?.focus();
    });
}

function commitSearch(): void {
    const input = picker.value?.querySelector('input');
    const category = (input?.value ?? search.value).trim();

    if (category) {
        setCategory(category);
    }
}

function handleFocusOut(event: FocusEvent): void {
    const nextTarget = event.relatedTarget;

    if (nextTarget instanceof Node && picker.value?.contains(nextTarget)) {
        return;
    }

    commitSearch();
    dropdownOpen.value = false;
}

function handleModelUpdate(nextValues: AcceptableInputValue[]): void {
    const nextCategory = nextValues.at(-1);

    emit('update:modelValue', nextCategory ? String(nextCategory) : '');
}

function handleSelect(value: AcceptableValue): void {
    if (Array.isArray(value) || value === null || value === undefined) {
        return;
    }

    setCategory(String(value));
}

function handleCreate(): void {
    if (canCreate.value) {
        setCategory(trimmedSearch.value);
    }
}
</script>

<template>
    <ListboxRoot
        :model-value="modelValue || undefined"
        highlight-on-hover
        @update:model-value="handleSelect"
    >
        <div ref="picker" class="relative" @focusout="handleFocusOut">
            <TagsInput
                :model-value="selectedValues"
                class="min-h-9 w-full border-input bg-transparent shadow-sm"
                @update:model-value="handleModelUpdate"
            >
                <TagsInputItem
                    v-if="modelValue"
                    :value="modelValue"
                    :disabled="disabled"
                    class="cursor-pointer"
                    @click="reopenPicker"
                >
                    <TagsInputItemText />
                    <TagsInputItemDelete />
                </TagsInputItem>
                <ListboxFilter v-model="search" as-child>
                    <TagsInputInput
                        v-if="!modelValue"
                        :placeholder="placeholder"
                        :disabled="disabled"
                        @focus="dropdownOpen = true"
                        @keydown.enter.prevent="handleCreate"
                        @keydown.down="dropdownOpen = true"
                    />
                </ListboxFilter>
            </TagsInput>

            <div
                v-if="dropdownOpen"
                class="absolute top-full z-50 mt-1 w-full rounded-lg border bg-popover p-1 text-popover-foreground shadow-md"
            >
                <ListboxContent
                    class="max-h-[220px] scroll-py-1 overflow-x-hidden overflow-y-auto"
                    tabindex="0"
                >
                    <ListboxItem
                        v-for="category in matchingCategories"
                        :key="category"
                        class="relative flex cursor-default items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-hidden select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                        :value="category"
                    >
                        <span class="truncate">{{ category }}</span>
                        <ListboxItemIndicator
                            class="ml-auto inline-flex items-center justify-center"
                        >
                            <CheckIcon class="h-4 w-4" />
                        </ListboxItemIndicator>
                    </ListboxItem>

                    <button
                        v-if="canCreate"
                        type="button"
                        class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                        @click="handleCreate"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        Create "{{ trimmedSearch }}"
                    </button>

                    <div
                        v-if="!canCreate && matchingCategories.length === 0"
                        class="px-2 py-1.5 text-xs text-muted-foreground"
                    >
                        No categories found.
                    </div>
                </ListboxContent>
            </div>
        </div>
    </ListboxRoot>
</template>
