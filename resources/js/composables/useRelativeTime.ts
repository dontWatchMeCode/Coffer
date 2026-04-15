import { ref, onUnmounted } from 'vue';

export function useRelativeTime(intervalMs = 60_000): {
    now: ReturnType<typeof ref<Date>>;
} {
    const now = ref(new Date());
    const timer = setInterval(() => {
        now.value = new Date();
    }, intervalMs);

    onUnmounted(() => clearInterval(timer));

    return { now };
}
