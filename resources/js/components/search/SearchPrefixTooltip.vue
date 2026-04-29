<script setup lang="ts">
import { Info } from 'lucide-vue-next';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

type PrefixItem = {
    prefix: string;
    label: string;
};

// Keep in sync with SearchPrefixes.php.
const prefixes: PrefixItem[] = [
    { prefix: 't:', label: 'Tasks' },
    { prefix: 'c:', label: 'Contacts' },
    { prefix: 'e:', label: 'Events' },
    { prefix: 'p:', label: 'Projects' },
    { prefix: 'b:', label: 'Bookmarks' },
    { prefix: 'n:', label: 'Notes' },
    { prefix: 'l:', label: 'Collections' },
];

const props = withDefaults(
    defineProps<{
        class?: string;
        side?: 'top' | 'bottom' | 'left' | 'right';
    }>(),
    {
        side: 'bottom',
    },
);
</script>

<template>
    <TooltipProvider :delay-duration="200">
        <Tooltip>
            <TooltipTrigger as-child>
                <button
                    type="button"
                    :class="
                        cn(
                            'inline-flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground',
                            props.class,
                        )
                    "
                >
                    <Info class="h-4 w-4" />
                </button>
            </TooltipTrigger>
            <TooltipContent
                :side="props.side"
                align="end"
                class="max-w-xs space-y-1.5 border border-border bg-zinc-950 text-zinc-50 shadow-lg dark:bg-zinc-900"
                :show-arrow="false"
            >
                <p class="font-medium">Search prefixes</p>
                <div class="space-y-0.5 text-xs">
                    <div
                        v-for="item in prefixes"
                        :key="item.prefix"
                        class="flex items-center gap-2"
                    >
                        <kbd
                            class="rounded border border-current/30 px-1 py-0.5 font-mono"
                        >
                            {{ item.prefix }}
                        </kbd>
                        <span>{{ item.label }}</span>
                    </div>
                </div>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
