import type { Ref } from 'vue';
import { ref, watch } from 'vue';

export type ViewMode = 'list' | 'grid';

const STORAGE_KEY = 'app-list-view-mode';

function getStoredViewMode(): ViewMode {
    if (typeof window === 'undefined') {
        return 'grid';
    }

    const stored = localStorage.getItem(STORAGE_KEY);

    return stored === 'list' ? 'list' : 'grid';
}

const sharedViewMode = ref<ViewMode>(getStoredViewMode());

watch(sharedViewMode, (mode) => {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(STORAGE_KEY, mode);
});

export function useViewMode(): {
    viewMode: Ref<ViewMode>;
} {
    return {
        viewMode: sharedViewMode,
    };
}
