import type { Ref } from 'vue';
import { ref, watch } from 'vue';

export type ViewMode = 'list' | 'grid';

const STORAGE_PREFIX = 'app-list-view-mode';

const refsByKey: Map<string, Ref<ViewMode>> | null =
    typeof window !== 'undefined' ? new Map() : null;

function getStorageKey(pageKey: string): string {
    return `${STORAGE_PREFIX}:${pageKey}`;
}

function getStoredViewMode(pageKey: string): ViewMode {
    if (typeof window === 'undefined') {
        return 'grid';
    }

    const stored = localStorage.getItem(getStorageKey(pageKey));

    return stored === 'list' ? 'list' : 'grid';
}

export function useViewMode(pageKey: string): {
    viewMode: Ref<ViewMode>;
} {
    if (refsByKey) {
        const existing = refsByKey.get(pageKey);

        if (existing) {
            return { viewMode: existing };
        }
    }

    const viewMode = ref<ViewMode>(getStoredViewMode(pageKey));

    if (typeof window !== 'undefined') {
        watch(viewMode, (mode) => {
            localStorage.setItem(getStorageKey(pageKey), mode);
        });

        refsByKey!.set(pageKey, viewMode);
    }

    return { viewMode };
}
