<script setup lang="ts">
const avatarColors = [
    'bg-blue-500',
    'bg-green-500',
    'bg-purple-500',
    'bg-orange-500',
    'bg-pink-500',
    'bg-teal-500',
    'bg-indigo-500',
    'bg-rose-500',
];

type Props = {
    name: string;
    size?: 'sm' | 'lg';
};

withDefaults(defineProps<Props>(), {
    size: 'sm',
});

function contactInitials(name: string): string {
    return name
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function avatarColor(name: string): string {
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    return avatarColors[Math.abs(hash) % avatarColors.length];
}
</script>

<template>
    <div
        class="flex shrink-0 items-center justify-center rounded-full font-semibold text-white"
        :class="[
            size === 'lg' ? 'h-16 w-16 text-xl' : 'h-10 w-10 text-sm',
            avatarColor(name ?? ''),
        ]"
    >
        {{ contactInitials(name ?? '?') }}
    </div>
</template>
