<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { THEMES, useAppearance } from '@/composables/useAppearance';
import type { ThemeMode } from '@/composables/useAppearance';

const { appearance, updateAppearance, theme, selectTheme } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const groups: { mode: ThemeMode; label: string }[] = [
    { mode: 'light', label: 'Light themes' },
    { mode: 'dark', label: 'Dark themes' },
];

const themesFor = (mode: ThemeMode) =>
    THEMES.filter((option) => option.mode === mode);
</script>

<template>
    <div class="space-y-5">
        <div class="inline-flex flex-wrap gap-1 border bg-card p-1">
            <button
                v-for="{ value, Icon, label } in tabs"
                :key="value"
                :aria-pressed="appearance === value"
                @click="updateAppearance(value)"
                :class="[
                    'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                    appearance === value
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                ]"
            >
                <component :is="Icon" class="-ml-1 h-4 w-4" />
                <span class="ml-1.5 text-sm">{{ label }}</span>
            </button>
        </div>

        <div
            v-for="group in groups"
            :key="group.mode"
            class="max-w-md space-y-2"
            role="radiogroup"
            :aria-labelledby="`theme-group-${group.mode}`"
        >
            <p
                :id="`theme-group-${group.mode}`"
                class="text-sm text-muted-foreground"
            >
                {{ group.label }}
            </p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                <button
                    v-for="option in themesFor(group.mode)"
                    :key="option.id"
                    :title="option.name"
                    role="radio"
                    :aria-checked="theme === option.id"
                    @click="selectTheme(option.id)"
                    :class="[
                        'border p-2 text-left transition-colors',
                        theme === option.id
                            ? 'border-primary bg-accent/40'
                            : 'border-border bg-card hover:border-input',
                    ]"
                >
                    <span class="flex gap-1" aria-hidden="true">
                        <span
                            v-for="swatch in option.swatches"
                            :key="swatch"
                            class="h-5 flex-1 border border-border"
                            :style="{ backgroundColor: swatch }"
                        />
                    </span>
                    <span class="mt-1.5 block text-sm">{{ option.name }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
