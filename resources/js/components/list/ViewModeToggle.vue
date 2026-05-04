<script setup lang="ts">
import { LayoutGrid, List } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import type { ViewMode } from '@/composables/useViewMode';

const viewMode = defineModel<ViewMode>('viewMode', { required: true });
</script>

<template>
    <div class="flex items-center gap-1 rounded-lg border bg-muted p-0.5">
        <Button
            variant="ghost"
            size="sm"
            :class="{ 'bg-background': viewMode === 'list' }"
            class="cursor-pointer hover:bg-background!"
            @click="viewMode = 'list'"
        >
            <List class="mr-1.5 h-3.5 w-3.5" />
            List
        </Button>
        <Button
            variant="ghost"
            size="sm"
            :class="{ 'bg-background': viewMode === 'grid' }"
            class="cursor-pointer hover:bg-background!"
            @click="viewMode = 'grid'"
        >
            <LayoutGrid class="mr-1.5 h-3.5 w-3.5" />
            Cards
        </Button>
    </div>
</template>
