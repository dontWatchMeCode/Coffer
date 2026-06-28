import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useListDetailOverlay(
    resourceKey: string,
    teamSlug: string,
    hasListData: boolean,
) {
    const openedFromList = ref(hasListData);

    function pendingKey(): string {
        return `${resourceKey}:${teamSlug}:pending-saved-item`;
    }

    function rememberSavedItem<T>(item: T): void {
        if (openedFromList.value && typeof window !== 'undefined') {
            window.sessionStorage.setItem(pendingKey(), JSON.stringify(item));
        }
    }

    function closeDetail(indexUrl: string): void {
        if (openedFromList.value && window.history.length > 1) {
            history.back();

            return;
        }

        router.visit(indexUrl);
    }

    function getPendingSavedItem<T>(): T | null {
        if (typeof window === 'undefined') {
            return null;
        }

        const raw = window.sessionStorage.getItem(pendingKey());

        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw) as T;
        } catch {
            window.sessionStorage.removeItem(pendingKey());

            return null;
        }
    }

    function clearPendingSavedItem(): void {
        if (typeof window !== 'undefined') {
            window.sessionStorage.removeItem(pendingKey());
        }
    }

    return {
        openedFromList,
        rememberSavedItem,
        closeDetail,
        getPendingSavedItem,
        clearPendingSavedItem,
    };
}
