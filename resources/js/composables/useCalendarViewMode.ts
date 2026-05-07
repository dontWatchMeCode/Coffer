import type { Ref } from 'vue';
import { ref, watch } from 'vue';

export type CalendarViewMode = 'calendar' | 'list';

const STORAGE_KEY = 'app-calendar-view-mode';

function getStoredCalendarViewMode(): CalendarViewMode {
    if (typeof window === 'undefined') {
        return 'list';
    }

    const stored = localStorage.getItem(STORAGE_KEY);

    return stored === 'calendar' ? 'calendar' : 'list';
}

const sharedCalendarViewMode = ref<CalendarViewMode>(
    getStoredCalendarViewMode(),
);

watch(sharedCalendarViewMode, (mode) => {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(STORAGE_KEY, mode);
});

export function useCalendarViewMode(): {
    viewMode: Ref<CalendarViewMode>;
} {
    return {
        viewMode: sharedCalendarViewMode,
    };
}
