import { router } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { onUnmounted, ref, watch } from 'vue';

export function useSearch(
    url: string,
    dataKey: string,
    initialSearch?: string,
): {
    searchQuery: Ref<string>;
} {
    const searchQuery = ref<string>(
        initialSearch ??
            new URLSearchParams(window.location.search).get('search') ??
            '',
    );

    let timeout: ReturnType<typeof setTimeout> | null = null;

    watch(searchQuery, (query) => {
        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout(() => {
            router.visit(url, {
                data: { search: query || undefined },
                only: [dataKey],
                reset: [dataKey],
                preserveScroll: true,
                preserveState: true,
            });
        }, 300);
    });

    onUnmounted(() => {
        if (timeout) {
            clearTimeout(timeout);
        }
    });

    return { searchQuery };
}
