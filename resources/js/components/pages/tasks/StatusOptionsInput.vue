<script setup lang="ts">
import {
    Activity,
    Ban,
    Check,
    CheckIcon,
    ChevronDown,
    ChevronUp,
    CircleHelp,
    Flag,
    GripVertical,
    Plus,
    Trash2,
    X,
} from 'lucide-vue-next';
import {
    ListboxContent,
    ListboxFilter,
    ListboxItem,
    ListboxItemIndicator,
    ListboxRoot,
} from 'reka-ui';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref } from 'vue';
import { TagsInput, TagsInputInput } from '@/components/ui/tags-input';
import { getTaskStatusMeta } from '@/lib/tasks';
import type { TaskStatusOption } from '@/types';

type Props = {
    name: string;
    options: TaskStatusOption[];
};

const props = defineProps<Props>();
const model = defineModel<TaskStatusOption[]>({ required: true });

const searchTerm = ref('');
const open = ref(false);
const picker = ref<HTMLElement | null>(null);
const defaultOptions: TaskStatusOption[] = [
    { value: 'planned', label: 'Planned' },
    { value: 'question', label: 'Question' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'on_hold', label: 'On Hold' },
    { value: 'completed', label: 'Completed' },
    { value: 'dropped', label: 'Dropped' },
];
const statusIcons = {
    flag: Flag,
    activity: Activity,
    ban: Ban,
    check: Check,
    trash: Trash2,
    help: CircleHelp,
};

const selectedLabels = computed(() =>
    model.value.map((option) => option.label),
);
const trimmedSearch = computed(() => searchTerm.value.trim());
const candidates = computed(() => {
    const selectedValues = new Set(model.value.map((option) => option.value));
    const term = trimmedSearch.value.toLowerCase();

    return [...props.options, ...defaultOptions]
        .filter(
            (option, index, options) =>
                options.findIndex((item) => item.value === option.value) ===
                    index && !selectedValues.has(option.value),
        )
        .filter(
            (option) =>
                term === '' || option.label.toLowerCase().includes(term),
        );
});
const canCreate = computed(
    () =>
        trimmedSearch.value !== '' &&
        !model.value.some(
            (option) => option.value === slugStatus(trimmedSearch.value),
        ) &&
        !model.value.some(
            (option) =>
                option.label.toLowerCase() ===
                trimmedSearch.value.toLowerCase(),
        ) &&
        !candidates.value.some(
            (option) =>
                option.label.toLowerCase() ===
                trimmedSearch.value.toLowerCase(),
        ),
);

function slugStatus(value: string): string {
    return value
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function addOption(option: TaskStatusOption): void {
    model.value = [...model.value, option];
    searchTerm.value = '';
    open.value = false;
}

function createOption(): void {
    if (!canCreate.value) {
        return;
    }

    addOption({
        value: slugStatus(trimmedSearch.value),
        label: trimmedSearch.value,
    });
}

function handleSelectedValues(nextValues: AcceptableValue): void {
    if (!Array.isArray(nextValues)) {
        return;
    }

    const nextLabels = nextValues.map((value) => String(value));
    const addedLabel = nextLabels.find(
        (label) => !selectedLabels.value.includes(label),
    );

    if (!addedLabel) {
        return;
    }

    const option = candidates.value.find(
        (candidate) => candidate.label === addedLabel,
    );

    if (option) {
        addOption(option);
    }
}

function moveOption(index: number, offset: -1 | 1): void {
    const targetIndex = index + offset;

    if (targetIndex < 0 || targetIndex >= model.value.length) {
        return;
    }

    const reordered = [...model.value];
    const [option] = reordered.splice(index, 1);
    reordered.splice(targetIndex, 0, option);
    model.value = reordered;
}

function removeOption(index: number): void {
    if (model.value.length <= 1) {
        return;
    }

    model.value = model.value.filter((_, optionIndex) => optionIndex !== index);
}

function handleFocusOut(event: FocusEvent): void {
    const nextTarget = event.relatedTarget;

    if (nextTarget instanceof Node && picker.value?.contains(nextTarget)) {
        return;
    }

    open.value = false;
}
</script>

<template>
    <ListboxRoot
        :model-value="selectedLabels"
        highlight-on-hover
        multiple
        @update:model-value="handleSelectedValues"
    >
        <input type="hidden" :name="`${name}_present`" value="1" />

        <div ref="picker" class="relative" @focusout="handleFocusOut">
            <TagsInput
                :model-value="[]"
                class="min-h-10 w-full border-input bg-transparent shadow-sm"
            >
                <ListboxFilter v-model="searchTerm" as-child>
                    <TagsInputInput
                        placeholder="Add status..."
                        @focus="open = true"
                        @keydown.enter.prevent="createOption"
                        @keydown.down="open = true"
                    />
                </ListboxFilter>
            </TagsInput>

            <div
                v-if="open"
                class="absolute z-50 mt-1 w-full rounded-lg border bg-popover p-1 text-popover-foreground shadow-md"
            >
                <ListboxContent
                    class="max-h-[220px] scroll-py-1 overflow-x-hidden overflow-y-auto"
                    tabindex="0"
                >
                    <ListboxItem
                        v-for="option in candidates"
                        :key="option.value"
                        class="relative flex cursor-default items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-hidden select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                        :value="option.label"
                    >
                        <span class="truncate">{{ option.label }}</span>
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
                        @click="createOption"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        Create "{{ trimmedSearch }}"
                    </button>

                    <div
                        v-if="!canCreate && candidates.length === 0"
                        class="px-2 py-1.5 text-xs text-muted-foreground"
                    >
                        No statuses found.
                    </div>
                </ListboxContent>
            </div>
        </div>
    </ListboxRoot>

    <div class="mt-3 space-y-2">
        <div
            v-for="(option, index) in model"
            :key="option.value"
            class="group flex items-center gap-2 rounded-md border bg-background px-2.5 py-2 text-sm shadow-xs"
        >
            <input
                type="hidden"
                :name="`${name}[${index}][value]`"
                :value="option.value"
            />
            <input
                type="hidden"
                :name="`${name}[${index}][label]`"
                :value="option.label"
            />

            <GripVertical class="h-4 w-4 shrink-0 text-muted-foreground/60" />
            <component
                :is="
                    statusIcons[
                        getTaskStatusMeta(option.value)
                            .icon as keyof typeof statusIcons
                    ]
                "
                class="h-4 w-4 shrink-0"
                :class="getTaskStatusMeta(option.value).badgeColor"
            />
            <span class="min-w-0 flex-1 truncate font-medium">
                {{ option.label }}
            </span>
            <code class="hidden text-xs text-muted-foreground sm:inline">
                {{ option.value }}
            </code>
            <div
                class="ml-auto flex items-center gap-1 opacity-60 transition-opacity group-hover:opacity-100"
            >
                <button
                    type="button"
                    class="rounded p-1 text-muted-foreground hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-30"
                    :disabled="index === 0"
                    aria-label="Move status earlier"
                    @click="moveOption(index, -1)"
                >
                    <ChevronUp class="h-3.5 w-3.5" />
                </button>
                <button
                    type="button"
                    class="rounded p-1 text-muted-foreground hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-30"
                    :disabled="index === model.length - 1"
                    aria-label="Move status later"
                    @click="moveOption(index, 1)"
                >
                    <ChevronDown class="h-3.5 w-3.5" />
                </button>
                <button
                    type="button"
                    class="rounded p-1 text-muted-foreground hover:bg-destructive/10 hover:text-destructive disabled:pointer-events-none disabled:opacity-30"
                    :disabled="model.length <= 1"
                    aria-label="Remove status"
                    @click="removeOption(index)"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>
