<script setup lang="ts">
import { Info } from 'lucide-vue-next';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { SEARCH_CATEGORIES } from '@/lib/search';
import { cn } from '@/lib/utils';

const prefixes = SEARCH_CATEGORIES.map((category) => ({
    prefix: `${category.prefix}:`,
    label: category.label,
}));

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
                class="max-w-xs space-y-1.5"
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
                    <div class="mt-1 flex items-center gap-2 border-t pt-1">
                        <kbd
                            class="rounded border border-current/30 px-1 py-0.5 font-mono"
                        >
                            #tag
                        </kbd>
                        <span>Filter by tag</span>
                    </div>
                </div>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
