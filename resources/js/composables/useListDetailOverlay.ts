import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

type SavedItem = { id: number };

type SyncedList<TItem extends SavedItem> = {
    prop: string;
    items: () => readonly TItem[] | null | undefined;
};

type ListDetailSync<TItem extends SavedItem> = {
    detailItem: () => TItem | null | undefined;
    lists: SyncedList<TItem>[];
};

export function useListDetailOverlay<TItem extends SavedItem = SavedItem>(
    resourceKey: string,
    teamSlug: string,
    hasListData: boolean,
    sync?: ListDetailSync<TItem>,
) {
    const openedFromList = ref(hasListData);

    function pendingKey(): string {
        return `${resourceKey}:${teamSlug}:pending-saved-item`;
    }

    function rememberSavedItem(item: TItem): void {
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

    function getPendingSavedItem(): TItem | null {
        if (typeof window === 'undefined') {
            return null;
        }

        const raw = window.sessionStorage.getItem(pendingKey());

        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw) as TItem;
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

    function replaceLoadedItem(item: TItem): boolean {
        let replaced = false;

        for (const list of sync?.lists ?? []) {
            if (
                !list.items()?.some((loadedItem) => loadedItem.id === item.id)
            ) {
                continue;
            }

            router.replaceProp(list.prop, (items: unknown) => {
                if (!Array.isArray(items)) {
                    return items;
                }

                return items.map((loadedItem: TItem) =>
                    loadedItem.id === item.id ? item : loadedItem,
                );
            });

            replaced = true;
        }

        return replaced;
    }

    function applyPendingSavedItem(): void {
        if (!sync || sync.detailItem()) {
            return;
        }

        if (sync.lists.every((list) => !list.items())) {
            return;
        }

        const item = getPendingSavedItem();

        if (!item || typeof item.id !== 'number') {
            clearPendingSavedItem();

            return;
        }

        replaceLoadedItem(item);
        clearPendingSavedItem();
    }

    function onSavedItem(item: TItem): void {
        rememberSavedItem(item);
        replaceLoadedItem(item);
    }

    if (sync) {
        watch(
            () => [
                sync.detailItem()?.id,
                ...sync.lists.map((list) => list.items()),
            ],
            () => applyPendingSavedItem(),
            { immediate: true, flush: 'post' },
        );
    }

    return {
        closeDetail,
        onSavedItem,
    };
}
