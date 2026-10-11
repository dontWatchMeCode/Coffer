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
    backHandler?: () => void;
}

const props = defineProps<Props>();

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key !== 'Escape' || (!props.backHref && !props.backHandler)) {
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

    if (props.backHandler) {
        props.backHandler();
    } else if (props.backHref) {
        router.visit(props.backHref);
    }
});
</script>

<template>
    <div class="border border-l-2 border-l-primary bg-card px-4 py-3.5">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex min-w-0 items-center gap-3">
                <Button
                    v-if="backHref || backHandler"
                    variant="ghost"
                    size="icon"
                    as-child
                    class="shrink-0"
                    :title="backLabel ?? 'Go back'"
                >
                    <a v-if="backHandler" href="#" @click.prevent="backHandler">
                        <ArrowLeft class="h-4 w-4" />
                    </a>
                    <Link v-else :href="backHref">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>

                <div class="flex min-w-0 flex-col items-start justify-center">
                    <h1
                        class="text-lg leading-tight font-semibold wrap-anywhere"
                    >
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

            <div
                v-if="$slots.actions"
                class="flex shrink-0 flex-wrap items-center justify-end gap-2"
            >
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
