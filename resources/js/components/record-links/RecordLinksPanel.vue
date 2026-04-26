<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Link2, Plus, Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';

type Props = {
    links: LinkRecord[];
    context: LinkContext;
    endpoints: LinkEndpoints;
};

const props = defineProps<Props>();

const query = ref('');
const candidates = ref<LinkRecord[]>([]);
const loading = ref(false);
const addingKey = ref<string | null>(null);
const removingKey = ref<string | null>(null);
const error = ref<string | null>(null);

const hasLinks = computed(() => props.links.length > 0);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(query, (newQuery) => {
    error.value = null;

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    if (!newQuery.trim()) {
        candidates.value = [];
        loading.value = false;

        return;
    }

    loading.value = true;

    debounceTimer = setTimeout(async () => {
        try {
            const url = new URL(
                props.endpoints.candidates,
                window.location.origin,
            );
            url.searchParams.set('q', newQuery.trim());
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
                candidates.value = (data.records ?? []).map(
                    (r: { id: number; type: string; title: string }) => ({
                        ...r,
                        url: '',
                    }),
                );
            }
        } catch {
            candidates.value = [];
        } finally {
            loading.value = false;
        }
    }, 200);
});

function linkKey(record: LinkRecord): string {
    return `${record.type}-${record.id}`;
}

async function addLink(record: LinkRecord): Promise<void> {
    addingKey.value = linkKey(record);
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
                to_type: record.type,
                to_id: record.id,
            }),
        });

        if (response.ok) {
            query.value = '';
            candidates.value = [];
            router.reload();
        } else {
            const data = await response.json().catch(() => ({}));
            error.value = data.message ?? 'Failed to add link.';
        }
    } catch {
        error.value = 'Failed to add link.';
    } finally {
        addingKey.value = null;
    }
}

async function removeLink(record: LinkRecord): Promise<void> {
    removingKey.value = linkKey(record);
    error.value = null;

    try {
        const url = new URL(props.endpoints.destroy, window.location.origin);
        url.searchParams.set('from_type', props.context.type);
        url.searchParams.set('from_id', String(props.context.id));
        url.searchParams.set('to_type', record.type);
        url.searchParams.set('to_id', String(record.id));

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
            error.value = data.message ?? 'Failed to remove link.';
        }
    } catch {
        error.value = 'Failed to remove link.';
    } finally {
        removingKey.value = null;
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
            <Link2 class="h-4 w-4 text-muted-foreground" />
            <h3
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Linked Records
            </h3>
        </div>

        <div v-if="hasLinks" class="space-y-1">
            <div
                v-for="link in links"
                :key="`${link.type}-${link.id}`"
                class="group flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-muted"
            >
                <a
                    :href="link.url"
                    class="flex min-w-0 flex-1 items-center gap-2 text-sm"
                >
                    <span class="truncate font-medium">{{ link.title }}</span>
                    <span
                        class="shrink-0 rounded bg-muted px-1 py-0.5 text-[10px] font-medium text-muted-foreground uppercase"
                    >
                        {{ link.type }}
                    </span>
                </a>

                <Button
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6 shrink-0"
                    :disabled="removingKey === linkKey(link)"
                    @click="removeLink(link)"
                >
                    <X class="h-3 w-3 text-muted-foreground" />
                </Button>
            </div>
        </div>

        <div v-else class="text-sm text-muted-foreground">
            No linked records yet.
        </div>

        <Separator />

        <div class="space-y-2">
            <div class="relative">
                <Search
                    class="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="query"
                    placeholder="Search to link..."
                    class="h-8 pl-8 text-sm"
                />
            </div>

            <div v-if="loading" class="text-xs text-muted-foreground">
                Searching...
            </div>

            <div
                v-else-if="query.trim() && candidates.length === 0"
                class="text-xs text-muted-foreground"
            >
                No results found.
            </div>

            <div v-else-if="candidates.length > 0" class="space-y-1">
                <div
                    v-for="candidate in candidates"
                    :key="`${candidate.type}-${candidate.id}`"
                    class="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-muted"
                >
                    <span class="min-w-0 flex-1 truncate text-sm">
                        {{ candidate.title }}
                    </span>
                    <span
                        class="shrink-0 rounded bg-muted px-1 py-0.5 text-[10px] font-medium text-muted-foreground uppercase"
                    >
                        {{ candidate.type }}
                    </span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6 shrink-0"
                        :disabled="addingKey === linkKey(candidate)"
                        @click="addLink(candidate)"
                    >
                        <Plus class="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>

            <div v-if="error" class="text-xs text-destructive">
                {{ error }}
            </div>
        </div>
    </div>
</template>
