<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CheckIcon, Plus, Tag } from 'lucide-vue-next';
import {
    ListboxContent,
    ListboxFilter,
    ListboxItem,
    ListboxItemIndicator,
    ListboxRoot,
} from 'reka-ui';
import type { AcceptableInputValue, AcceptableValue } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { Separator } from '@/components/ui/separator';
import {
    TagsInput,
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
} from '@/components/ui/tags-input';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    tags: RecordTag[];
    context: TagContext;
    endpoints: TagEndpoints;
};

const props = defineProps<Props>();

const searchTerm = ref('');
const candidates = ref<RecordTag[]>([]);
const loading = ref(false);
const open = ref(false);
const addingValue = ref<string | null>(null);
const removingId = ref<number | null>(null);
const error = ref<string | null>(null);

const tagNames = computed(() => props.tags.map((tag) => tag.name));
const hasTags = computed(() => props.tags.length > 0);
const trimmedSearch = computed(() => searchTerm.value.trim());
const exactCandidate = computed(() =>
    candidates.value.some(
        (tag) => tag.name.toLowerCase() === trimmedSearch.value.toLowerCase(),
    ),
);
const attachedSearch = computed(() =>
    props.tags.some(
        (tag) => tag.name.toLowerCase() === trimmedSearch.value.toLowerCase(),
    ),
);
const canCreate = computed(
    () =>
        trimmedSearch.value !== '' &&
        !exactCandidate.value &&
        !attachedSearch.value,
);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(searchTerm, (newSearchTerm) => {
    error.value = null;

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    if (!newSearchTerm.trim()) {
        candidates.value = [];
        loading.value = false;
        open.value = false;

        return;
    }

    loading.value = true;
    open.value = true;

    debounceTimer = setTimeout(async () => {
        try {
            const url = new URL(
                props.endpoints.candidates,
                window.location.origin,
            );
            url.searchParams.set('q', newSearchTerm.trim());
            url.searchParams.set('from_type', props.context.type);
            url.searchParams.set('from_id', String(props.context.id));

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const data = await response.json();
                candidates.value = data.tags ?? [];
            }
        } catch {
            candidates.value = [];
        } finally {
            loading.value = false;
        }
    }, 200);
});

async function addTag(payload: {
    tag_id?: number;
    name?: string;
}): Promise<void> {
    const value = payload.name ?? String(payload.tag_id);
    addingValue.value = value;
    error.value = null;

    try {
        const response = await fetch(props.endpoints.store, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                from_type: props.context.type,
                from_id: props.context.id,
                ...payload,
            }),
        });

        if (response.ok) {
            searchTerm.value = '';
            candidates.value = [];
            open.value = false;
            router.reload();
        } else {
            const data = await response.json().catch(() => ({}));
            error.value = data.message ?? 'Failed to add tag.';
        }
    } catch {
        error.value = 'Failed to add tag.';
    } finally {
        addingValue.value = null;
    }
}

async function removeTag(tag: RecordTag): Promise<void> {
    removingId.value = tag.id;
    error.value = null;

    try {
        const url = new URL(props.endpoints.destroy, window.location.origin);
        url.searchParams.set('from_type', props.context.type);
        url.searchParams.set('from_id', String(props.context.id));
        url.searchParams.set('tag_id', String(tag.id));

        const response = await fetch(url.toString(), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });

        if (response.ok) {
            router.reload();
        } else {
            const data = await response.json().catch(() => ({}));
            error.value = data.message ?? 'Failed to remove tag.';
        }
    } catch {
        error.value = 'Failed to remove tag.';
    } finally {
        removingId.value = null;
    }
}

function handleModelValueUpdate(nextValues: AcceptableInputValue[]): void {
    const nextNames = nextValues.map((value) => String(value));
    const removed = props.tags.find((tag) => !nextNames.includes(tag.name));

    if (removed) {
        void removeTag(removed);
    }
}

function handleSelectedValues(nextValues: AcceptableValue): void {
    if (!Array.isArray(nextValues)) {
        return;
    }

    const nextNames = nextValues.map((value) => String(value));
    const addedName = nextNames.find((name) => !tagNames.value.includes(name));

    if (!addedName) {
        return;
    }

    const tag = candidates.value.find(
        (candidate) => candidate.name === addedName,
    );

    if (tag) {
        void addTag({ tag_id: tag.id });
    }
}

function handleCreate(): void {
    if (canCreate.value) {
        void addTag({ name: trimmedSearch.value });
    }
}

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    if (match) {
        return decodeURIComponent(match[1]);
    }

    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta?.getAttribute('content') ?? '';
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <Tag class="h-4 w-4 text-muted-foreground" />
            <h3
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Tags
            </h3>
        </div>

        <ListboxRoot
            :model-value="tagNames"
            highlight-on-hover
            multiple
            @update:model-value="handleSelectedValues"
        >
            <div class="relative">
                <TagsInput
                    :model-value="tagNames"
                    class="min-h-8 w-full"
                    @update:model-value="handleModelValueUpdate"
                >
                    <TagsInputItem
                        v-for="tag in tags"
                        :key="tag.id"
                        :value="tag.name"
                        :disabled="removingId === tag.id"
                    >
                        <TagsInputItemText />
                        <TagsInputItemDelete />
                    </TagsInputItem>
                    <ListboxFilter v-model="searchTerm" as-child>
                        <TagsInputInput
                            placeholder="Search tags..."
                            @focus="open = trimmedSearch !== ''"
                            @keydown.enter.prevent="handleCreate"
                            @keydown.down="open = true"
                        />
                    </ListboxFilter>
                </TagsInput>

                <div
                    v-if="open"
                    class="absolute z-50 mt-1 w-full rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
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
                            v-for="tag in candidates"
                            :key="tag.id"
                            class="relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-hidden select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                            :value="tag.name"
                        >
                            <span class="truncate">{{ tag.name }}</span>
                            <ListboxItemIndicator
                                class="ml-auto inline-flex items-center justify-center"
                            >
                                <CheckIcon class="h-4 w-4" />
                            </ListboxItemIndicator>
                        </ListboxItem>

                        <button
                            v-if="canCreate"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                            :disabled="addingValue === trimmedSearch"
                            @click="handleCreate"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Create "{{ trimmedSearch }}"
                        </button>

                        <div
                            v-if="!canCreate && candidates.length === 0"
                            class="px-2 py-1.5 text-xs text-muted-foreground"
                        >
                            No tags found.
                        </div>
                    </ListboxContent>
                </div>
            </div>
        </ListboxRoot>

        <div v-if="!hasTags" class="text-sm text-muted-foreground">
            No tags yet.
        </div>

        <div v-if="error" class="text-xs text-destructive">
            {{ error }}
        </div>

        <Separator />
    </div>
</template>
