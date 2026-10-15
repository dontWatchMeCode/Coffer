<script setup lang="ts">
import { useWindowVirtualizer } from '@tanstack/vue-virtual';
import { Download, File, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import type { ComponentPublicInstance } from 'vue';
import EmptyState from '@/components/list/EmptyState.vue';
import FileKindIcon from '@/components/pages/files/FileKindIcon.vue';
import { Button } from '@/components/ui/button';
import { formatBytes } from '@/lib/utils';
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
const failedPreviews = ref(new Set<number>());

watch(
    () => props.files,
    () => {
        failedPreviews.value.clear();
    },
);

function previewImageUrl(file: FileItem): string | null {
    if (failedPreviews.value.has(file.id)) {
        return null;
    }

    return file.previewKind === 'image' ? file.previewUrl : file.thumbnailUrl;
}

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
    const file = props.files[index];
    const ratio =
        file?.previewKind === 'image' && file.width && file.height
            ? file.height / file.width
            : 0.75;

    return Math.round(columnWidth.value * ratio + cardChrome);
}

function imageAspectRatio(file: FileItem): string {
    return file.previewKind === 'image' && file.width && file.height
        ? `${file.width} / ${file.height}`
        : '4 / 3';
}

const virtualizer = useWindowVirtualizer(
    computed(() => ({
        count: props.files.length,
        estimateSize,
        getItemKey: (index: number) => props.files[index].id,
        gap,
        lanes: lanes.value,
        overscan: 8,
        scrollMargin: scrollMargin.value,
    })),
);

const virtualItems = computed(() => virtualizer.value.getVirtualItems());
const totalSize = computed(() => virtualizer.value.getTotalSize());

function measureItem(element: Element | ComponentPublicInstance | null): void {
    virtualizer.value.measureElement(
        element instanceof HTMLElement ? element : null,
    );
}

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
    <div v-if="files.length > 0" ref="boardRef" class="pr-1">
        <div class="relative w-full" :style="{ height: `${totalSize}px` }">
            <div
                v-for="virtualItem in virtualItems"
                :key="String(virtualItem.key)"
                :ref="measureItem"
                :data-index="virtualItem.index"
                :style="itemStyle(virtualItem.lane, virtualItem.start)"
            >
                <article
                    data-testid="file-board-item"
                    class="group cursor-pointer overflow-hidden border bg-card transition-colors hover:border-primary"
                    @click="navigateToFile(files[virtualItem.index])"
                >
                    <div
                        class="relative bg-muted"
                        :style="{
                            aspectRatio: imageAspectRatio(
                                files[virtualItem.index],
                            ),
                        }"
                    >
                        <img
                            v-if="previewImageUrl(files[virtualItem.index])"
                            :src="
                                previewImageUrl(files[virtualItem.index]) ??
                                undefined
                            "
                            :alt="files[virtualItem.index].title"
                            loading="lazy"
                            class="h-full w-full object-cover"
                            @error="
                                failedPreviews.add(files[virtualItem.index].id)
                            "
                        />
                        <div
                            v-else
                            class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4 text-muted-foreground"
                        >
                            <FileKindIcon
                                :kind="files[virtualItem.index].previewKind"
                                class="h-12 w-12"
                            />
                            <p class="max-w-full truncate text-xs">
                                {{ files[virtualItem.index].originalName }}
                            </p>
                            <p class="text-xs">
                                {{ formatBytes(files[virtualItem.index].size) }}
                            </p>
                        </div>
                        <span
                            v-if="
                                files[virtualItem.index].previewKind === 'video'
                            "
                            class="pointer-events-none absolute bottom-2 left-2 flex items-center gap-1 rounded bg-black/65 px-2 py-1 text-xs text-white"
                        >
                            <FileKindIcon kind="video" class="h-3 w-3" />
                            Video
                        </span>
                        <div
                            class="absolute inset-x-0 top-0 flex justify-end gap-1 bg-gradient-to-b from-black/45 to-transparent p-2 opacity-0 transition group-hover:opacity-100"
                        >
                            <Button
                                as="a"
                                :href="files[virtualItem.index].downloadUrl"
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
                                    openDeleteDialog(files[virtualItem.index])
                                "
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div class="space-y-1 p-3">
                        <h2 class="line-clamp-2 text-sm font-medium">
                            {{ files[virtualItem.index].title }}
                        </h2>
                        <p
                            v-if="files[virtualItem.index].description"
                            class="line-clamp-2 text-xs text-muted-foreground"
                        >
                            {{ files[virtualItem.index].description }}
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <EmptyState
        v-else
        :title="searchQuery ? 'No files match your search.' : 'No files yet.'"
        :description="
            searchQuery
                ? 'Try another title, filename, or description.'
                : 'Upload your first private image, video, or document.'
        "
        :show-action="!searchQuery"
        action-label="Add your first file"
        @action="openCreateDialog"
    >
        <template #icon>
            <File class="h-12 w-12" />
        </template>
    </EmptyState>
</template>
