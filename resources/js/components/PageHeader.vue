<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { ArrowLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface Props {
    title: string;
    subtitle?: string;
    description?: string;
    backHref?: string;
    backLabel?: string;
}

const props = defineProps<Props>();

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key !== 'Escape' || !props.backHref) {
        return;
    }

    const target = event.target as HTMLElement;

    if (
        target.closest('[data-radix-popper-content-wrapper]') ||
        target.closest('[role="dialog"]') ||
        target.closest('[data-state="open"]')
    ) {
        return;
    }

    router.visit(props.backHref);
});
</script>

<template>
    <div class="border-b px-4 py-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center"
        >
            <div class="flex items-center gap-3">
                <Button
                    v-if="backHref"
                    variant="ghost"
                    size="icon"
                    as-child
                    class="shrink-0"
                    :title="backLabel ?? 'Go back'"
                >
                    <Link :href="backHref">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>

                <div
                    class="flex min-h-[50px] min-w-0 flex-col items-start justify-center"
                >
                    <h1 class="text-xl leading-tight font-semibold">
                        {{ title }}
                        <span v-if="subtitle" class="text-muted-foreground">
                            {{ subtitle }}</span
                        >
                    </h1>
                    <p
                        v-if="description"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
            </div>

            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2 justify-end">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
