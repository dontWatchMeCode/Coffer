<script setup lang="ts">
type Props = {
    selectedProgress: number;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:selectedProgress': [value: number];
    change: [value: number];
}>();

const progressMarkers = Array.from({ length: 11 }, (_, index) => index * 10);

function progressMarkerPosition(marker: number): string {
    if (marker === 0) {
        return '0.1875rem';
    }

    if (marker === 100) {
        return 'calc(100% - 0.1875rem)';
    }

    return `${marker}%`;
}

function progressFillWidth(progress: number): string {
    if (progress <= 0) {
        return '0%';
    }

    if (progress >= 100) {
        return '100%';
    }

    return `calc(${progress}% + 0.1875rem)`;
}
</script>

<template>
    <div>
        <h3
            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Progress
        </h3>
        <div class="mb-3 flex items-center gap-2">
            <div
                class="group relative h-2 flex-1 overflow-hidden rounded-full bg-muted"
            >
                <div
                    class="pointer-events-none absolute inset-0 z-0 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <div
                        v-for="marker in progressMarkers"
                        :key="marker"
                        class="absolute top-1/2 h-1.5 w-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-foreground/20"
                        :style="{ left: progressMarkerPosition(marker) }"
                    />
                </div>
                <div
                    class="absolute inset-y-0 left-0 z-10 rounded-full bg-primary transition-all"
                    :style="{ width: progressFillWidth(selectedProgress) }"
                />
                <input
                    :value="selectedProgress"
                    type="range"
                    min="0"
                    max="100"
                    step="10"
                    class="absolute inset-0 z-20 h-2 w-full cursor-pointer appearance-none bg-transparent opacity-0"
                    @input="
                        emit(
                            'update:selectedProgress',
                            Number(($event.target as HTMLInputElement).value),
                        )
                    "
                    @change="
                        emit(
                            'change',
                            Number(($event.target as HTMLInputElement).value),
                        )
                    "
                />
            </div>
            <span class="w-10 text-right text-sm leading-none"
                >{{ selectedProgress }}%</span
            >
        </div>
    </div>
</template>
