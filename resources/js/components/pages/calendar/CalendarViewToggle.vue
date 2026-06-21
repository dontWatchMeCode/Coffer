<script setup lang="ts">
import { CalendarDays, List } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useCalendarViewMode } from '@/composables/useCalendarViewMode';

const { viewMode } = useCalendarViewMode();
</script>

<template>
    <div class="flex items-center gap-1 rounded-lg border bg-muted p-0.5">
        <Button
            variant="ghost"
            size="sm"
            :class="{ 'bg-background': viewMode === 'calendar' }"
            class="cursor-pointer hover:bg-background!"
            @click="viewMode = 'calendar'"
        >
            <CalendarDays class="mr-1.5 h-3.5 w-3.5" />
            Calendar
        </Button>
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
    </div>
</template>
