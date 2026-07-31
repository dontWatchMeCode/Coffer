<script setup lang="ts">
import {
    Bookmark,
    CalendarDays,
    Contact,
    CreditCard,
    FileText,
    FolderGit2,
    Layers3,
    ListTodo,
    ScrollText,
    Search,
    Table2,
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

type TagItem = { id: number; name: string; slug: string };
type TypeOption = { value: string; label: string; prefix: string };

const props = defineProps<{
    types: TypeOption[];
    tags: TagItem[];
    activeType: string;
    activeTag: string;
}>();

const emit = defineEmits<{
    'update:activeType': [value: string];
    'update:activeTag': [value: string];
    clearFilters: [];
}>();

const typeIconMap: Record<string, typeof Search> = {
    tasks: ListTodo,
    contacts: Contact,
    events: CalendarDays,
    projects: FolderGit2,
    bookmarks: Bookmark,
    subscriptions: CreditCard,
    notes: FileText,
    collections: Layers3,
    log_entries: ScrollText,
    spreadsheets: Table2,
};

function setType(typeValue: string): void {
    emit('update:activeType', props.activeType === typeValue ? '' : typeValue);
}

function setTag(tagSlug: string): void {
    emit('update:activeTag', props.activeTag === tagSlug ? '' : tagSlug);
}
</script>

<template>
    <div class="py-6 first:pt-1">
        <p
            class="mb-3 text-xs font-medium tracking-wider text-muted-foreground uppercase"
        >
            Record type
        </p>
        <div class="space-y-0.5">
            <button
                v-for="typeOption in types"
                :key="typeOption.value"
                class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors"
                :class="{
                    'bg-accent font-medium text-accent-foreground':
                        activeType === typeOption.value,
                    'text-muted-foreground hover:bg-accent/50 hover:text-foreground':
                        activeType !== typeOption.value,
                }"
                @click="setType(typeOption.value)"
            >
                <component
                    :is="typeIconMap[typeOption.value] ?? Search"
                    class="h-3.5 w-3.5"
                />
                {{ typeOption.label }}
            </button>
        </div>
    </div>

    <Separator />

    <div class="py-6">
        <p
            class="mb-3 text-xs font-medium tracking-wider text-muted-foreground uppercase"
        >
            Tags
        </p>
        <div v-if="tags.length === 0" class="text-xs text-muted-foreground">
            No tags yet
        </div>
        <div v-else class="flex flex-wrap gap-1.5">
            <button
                v-for="tagItem in tags"
                :key="tagItem.slug"
                @click="setTag(tagItem.slug)"
            >
                <Badge
                    :variant="
                        activeTag === tagItem.slug ? 'default' : 'outline'
                    "
                    class="cursor-pointer text-xs"
                >
                    {{ tagItem.name }}
                </Badge>
            </button>
        </div>
    </div>

    <template v-if="activeType || activeTag">
        <Separator />
        <div class="py-6">
            <button
                class="text-xs text-muted-foreground hover:text-foreground"
                @click="emit('clearFilters')"
            >
                Clear filters
            </button>
        </div>
    </template>
</template>
