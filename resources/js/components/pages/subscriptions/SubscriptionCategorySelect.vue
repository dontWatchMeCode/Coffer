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
import { computed, nextTick, ref, watch } from 'vue';
import {
    TagsInput,
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
} from '@/components/ui/tags-input';
import type { SubscriptionCategory } from '@/types';

type Props = {
    modelValue?: string | null;
    categories?: SubscriptionCategory[];
    candidatesUrl?: string;
    placeholder?: string;
    disabled?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    categories: () => [],
    candidatesUrl: '',
    placeholder: 'Category',
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const categorySearch = ref('');
const categoryDropdownOpen = ref(false);
const pickerRef = ref<HTMLElement | null>(null);
const candidates = ref<SubscriptionCategory[]>([]);
const loading = ref(false);

const displayValues = computed(() =>
    props.modelValue ? [props.modelValue] : [],
);

const trimmedSearch = computed(() => categorySearch.value.trim());

const allOptions = computed(() => {
    const existing = props.categories.map((c) => c.name);
    const extra = candidates.value
        .filter((c) => !existing.includes(c.name))
        .map((c) => c.name);

    return [...existing, ...extra];
});

const matchingOptions = computed(() => {
    const search = trimmedSearch.value.toLowerCase();

    if (!search) {
        return allOptions.value;
    }

    return allOptions.value.filter((name) =>
        name.toLowerCase().includes(search),
    );
});

const hasExactMatch = computed(() =>
    allOptions.value.some(
        (name) => name.toLowerCase() === trimmedSearch.value.toLowerCase(),
    ),
);

const canCreate = computed(
    () => trimmedSearch.value !== '' && !hasExactMatch.value,
);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(categorySearch, (term) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    if (!term.trim()) {
        loading.value = false;
        categoryDropdownOpen.value =
            props.modelValue === null && allOptions.value.length > 0;

        return;
    }

    categoryDropdownOpen.value = true;

    if (!props.candidatesUrl) {
        return;
    }

    loading.value = true;

    debounceTimer = setTimeout(async () => {
        try {
            const url = new URL(props.candidatesUrl, window.location.origin);
            url.searchParams.set('q', term.trim());

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const data = await response.json();
                candidates.value = data.categories ?? [];
            }
        } catch {
            candidates.value = [];
        } finally {
            loading.value = false;
        }
    }, 200);
});

function setCategory(value: string): void {
    emit('update:modelValue', value);
    categorySearch.value = '';
    categoryDropdownOpen.value = false;
}

function clear(): void {
    emit('update:modelValue', null);
}

function reopen(event: MouseEvent): void {
    event.stopPropagation();
    clear();
    categoryDropdownOpen.value = true;

    nextTick(() => {
        const input = pickerRef.value?.querySelector('input');
        input?.focus();
    });
}

function handleModelValueUpdate(nextValues: AcceptableInputValue[]): void {
    if (nextValues.length === 0 && props.modelValue) {
        clear();
    }
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

function handleFocusOut(event: FocusEvent): void {
    const nextTarget = event.relatedTarget;

    if (nextTarget instanceof Node && pickerRef.value?.contains(nextTarget)) {
        return;
    }

    categoryDropdownOpen.value = false;
}
</script>

<template>
    <ListboxRoot
        :model-value="modelValue || undefined"
        highlight-on-hover
        @update:model-value="handleSelect"
    >
        <div ref="pickerRef" class="relative" @focusout="handleFocusOut">
            <TagsInput
                :model-value="displayValues"
                class="min-h-8 w-full border-muted bg-background/60 shadow-none"
                @update:model-value="handleModelValueUpdate"
            >
                <TagsInputItem
                    v-if="modelValue"
                    :value="modelValue"
                    :disabled="disabled"
                    class="cursor-pointer"
                    @click="reopen"
                >
                    <TagsInputItemText />
                    <TagsInputItemDelete />
                </TagsInputItem>
                <ListboxFilter v-model="categorySearch" as-child>
                    <TagsInputInput
                        v-if="!modelValue"
                        :placeholder="placeholder"
                        :disabled="disabled"
                        @focus="categoryDropdownOpen = true"
                        @keydown.enter.prevent="handleCreate"
                        @keydown.down="categoryDropdownOpen = true"
                    />
                </ListboxFilter>
            </TagsInput>

            <div
                v-if="categoryDropdownOpen && !modelValue"
                class="absolute z-50 mt-1 w-full rounded-lg border bg-popover p-1 text-popover-foreground shadow-md"
            >
                <div
                    v-if="loading"
                    class="px-2 py-1.5 text-xs text-muted-foreground"
                >
                    Searching...
                </div>

                <ListboxContent
                    v-else
                    class="max-h-[220px] scroll-py-1 overflow-x-hidden overflow-y-auto"
                    tabindex="0"
                >
                    <ListboxItem
                        v-for="name in matchingOptions"
                        :key="name"
                        class="relative flex cursor-default items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-hidden select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                        :value="name"
                    >
                        <span class="truncate">{{ name }}</span>
                        <ListboxItemIndicator
                            v-if="modelValue === name"
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
                        v-if="!canCreate && matchingOptions.length === 0"
                        class="px-2 py-1.5 text-xs text-muted-foreground"
                    >
                        No categories found.
                    </div>
                </ListboxContent>
            </div>
        </div>
    </ListboxRoot>
</template>
