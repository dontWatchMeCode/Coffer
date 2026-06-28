<script setup lang="ts">
import { useWindowVirtualizer } from '@tanstack/vue-virtual';
import { Download, Image, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import EmptyState from '@/components/list/EmptyState.vue';
import { Button } from '@/components/ui/button';
import type { FileItem } from '@/types';

const props = defineProps<{
    files: FileItem[];
    searchQuery: string;
    navigateToFile: (file: FileItem) => void;
    openDeleteDialog: (file: FileItem) => void;
    openCreateDialog: () => void;
}>();

const boardRef = ref<HTMLElement | null>(null);
const scrollMargin = ref(0);
const lanes = ref(1);
const columnWidth = ref(320);
const gap = 16;
const cardChrome = 92;

const imageFiles = computed(() => props.files.filter((file) => file.isImage));

function updateLanes(): void {
    const width = window.innerWidth;

    lanes.value = width >= 1280 ? 4 : width >= 1024 ? 3 : width >= 640 ? 2 : 1;
}

function updateScrollMargin(): void {
    scrollMargin.value = boardRef.value?.offsetTop ?? 0;
}

function updateColumnWidth(): void {
    const boardWidth = boardRef.value?.clientWidth ?? 0;

    columnWidth.value = Math.max(
        200,
        (boardWidth - gap * (lanes.value - 1)) / lanes.value,
    );
}

function estimateSize(index: number): number {
    const file = imageFiles.value[index];
    const ratio = file?.width && file.height ? file.height / file.width : 0.75;

    return Math.round(columnWidth.value * ratio + cardChrome);
}

function imageAspectRatio(file: FileItem): string {
    return file.width && file.height
        ? `${file.width} / ${file.height}`
        : '4 / 3';
}

const virtualizer = useWindowVirtualizer(
    computed(() => ({
        count: imageFiles.value.length,
        estimateSize,
        lanes: lanes.value,
        overscan: 8,
        scrollMargin: scrollMargin.value,
    })),
);

const virtualItems = computed(() => virtualizer.value.getVirtualItems());
const totalSize = computed(() => virtualizer.value.getTotalSize());

function itemStyle(lane: number, start: number): Record<string, string> {
    const width = `calc((100% - ${gap * (lanes.value - 1)}px) / ${lanes.value})`;
    const left = `calc(((${width}) + ${gap}px) * ${lane})`;

    return {
        position: 'absolute',
        top: '0',
        left,
        width,
        transform: `translateY(${start - scrollMargin.value}px)`,
    };
}

function handleResize(): void {
    updateLanes();
    updateScrollMargin();
    updateColumnWidth();
    virtualizer.value.measure();
}

onMounted(() => {
    updateLanes();
    updateScrollMargin();
    updateColumnWidth();
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<template>
    <div v-if="imageFiles.length > 0" ref="boardRef" class="pr-1">
        <div class="relative w-full" :style="{ height: `${totalSize}px` }">
            <div
                v-for="virtualItem in virtualItems"
                :key="String(virtualItem.key)"
                :data-index="virtualItem.index"
                :style="itemStyle(virtualItem.lane, virtualItem.start)"
            >
                <article
                    class="group mb-4 cursor-pointer overflow-hidden rounded-xl border bg-card shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-card/50"
                    @click="navigateToFile(imageFiles[virtualItem.index])"
                >
                    <div
                        class="relative bg-muted"
                        :style="{
                            aspectRatio: imageAspectRatio(
                                imageFiles[virtualItem.index],
                            ),
                        }"
                    >
                        <img
                            :src="imageFiles[virtualItem.index].previewUrl"
                            :alt="imageFiles[virtualItem.index].title"
                            loading="lazy"
                            class="h-full w-full object-cover"
                        />
                        <div
                            class="absolute inset-x-0 top-0 flex justify-end gap-1 bg-gradient-to-b from-black/45 to-transparent p-2 opacity-0 transition group-hover:opacity-100"
                        >
                            <Button
                                as="a"
                                :href="
                                    imageFiles[virtualItem.index].downloadUrl
                                "
                                variant="secondary"
                                size="icon"
                                class="h-8 w-8"
                                aria-label="Download file"
                                @click.stop
                            >
                                <Download class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="secondary"
                                size="icon"
                                class="h-8 w-8"
                                aria-label="Delete file"
                                @click.stop="
                                    openDeleteDialog(
                                        imageFiles[virtualItem.index],
                                    )
                                "
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div class="space-y-1 p-3">
                        <h2 class="line-clamp-2 text-sm font-medium">
                            {{ imageFiles[virtualItem.index].title }}
                        </h2>
                        <p
                            v-if="imageFiles[virtualItem.index].description"
                            class="line-clamp-2 text-xs text-muted-foreground"
                        >
                            {{ imageFiles[virtualItem.index].description }}
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <div
        v-else-if="files.length > 0"
        class="rounded-lg border bg-card p-8 text-center text-sm text-muted-foreground"
    >
        No image previews in the loaded files. Switch to list view to review
        filenames and downloads.
    </div>

    <EmptyState
        v-else
        :title="
            searchQuery
                ? 'No image files match your search.'
                : 'No image files yet.'
        "
        :description="
            searchQuery
                ? 'Switch to text view to see non-image files or try another search.'
                : 'Upload your first private image to start the board.'
        "
        :show-action="!searchQuery"
        action-label="Add your first file"
        @action="openCreateDialog"
    >
        <template #icon>
            <Image class="h-12 w-12" />
        </template>
    </EmptyState>
</template>
