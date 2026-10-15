<script setup lang="ts">
import { ref, watch } from 'vue';
import type { FileItem } from '@/types';

const props = defineProps<{ file: FileItem }>();
const videoFailed = ref(false);

watch(
    () => props.file.previewUrl,
    () => {
        videoFailed.value = false;
    },
);
</script>

<template>
    <div class="space-y-3" data-testid="file-preview">
        <div
            v-if="file.previewKind === 'image' && file.previewUrl"
            class="overflow-hidden rounded-xl border bg-muted"
        >
            <img
                :src="file.previewUrl"
                :alt="file.title"
                class="mx-auto max-h-[70vh] w-auto object-contain"
            />
        </div>
        <template v-else-if="file.previewKind === 'video' && file.previewUrl">
            <video
                :key="file.previewUrl"
                :src="file.previewUrl"
                controls
                playsinline
                preload="metadata"
                :aria-label="file.title"
                class="max-h-[70vh] w-full rounded-xl border bg-black"
                @error="videoFailed = true"
            >
                Your browser cannot play this video. Download it to watch
                locally.
            </video>
            <p class="text-sm text-muted-foreground">
                {{
                    videoFailed
                        ? 'This video could not be played. Its codec may not be supported by your browser.'
                        : 'Video playback depends on browser and codec support.'
                }}
                <a :href="file.downloadUrl" class="underline">Download video</a>
            </p>
        </template>
        <template v-else-if="file.previewKind === 'text' && file.textPreview">
            <pre
                class="max-h-[65vh] overflow-auto rounded-xl border bg-muted p-4 text-sm [overflow-wrap:anywhere] whitespace-pre-wrap"
                >{{ file.textPreview.content }}</pre
            >
            <p
                v-if="file.textPreview.truncated"
                class="text-sm text-muted-foreground"
            >
                Showing the first 64 KB. Download the file to read the rest.
            </p>
        </template>
        <div
            v-else
            class="rounded-xl border bg-card p-8 text-center text-sm text-muted-foreground"
        >
            Preview is not available for this file. Download it to open locally.
        </div>
    </div>
</template>
