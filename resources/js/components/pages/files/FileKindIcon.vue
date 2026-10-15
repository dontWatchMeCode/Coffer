<script setup lang="ts">
import { File, FileText, Film, Image } from 'lucide-vue-next';
import type { FileItem } from '@/types';

defineProps<{ kind: FileItem['previewKind'] }>();

const icons = { image: Image, video: Film, text: FileText };
</script>

<template>
    <component :is="kind ? icons[kind] : File" />
</template>
