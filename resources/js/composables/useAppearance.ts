import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
    theme: Ref<ThemeId>;
    selectTheme: (value: ThemeId) => void;
};

export type ThemeMode = 'light' | 'dark';

export type ThemeId =
    | 'daybreak'
    | 'parchment'
    | 'glacier'
    | 'sage'
    | 'rose-pine-dawn'
    | 'porcelain'
    | 'dracula'
    | 'nord'
    | 'rose-pine'
    | 'espresso'
    | 'abyss'
    | 'gruvbox';

export type ThemeOption = {
    id: ThemeId;
    mode: ThemeMode;
    name: string;
    /** [background, primary, accent] preview swatches. */
    swatches: [string, string, string];
};

/**
 * Client-side mirror of AppearanceComposer::THEMES. Keep ids, modes, and
 * swatches in sync with the PHP list and the palettes in app.css.
 */
export const THEMES: readonly ThemeOption[] = [
    {
        id: 'daybreak',
        mode: 'light',
        name: 'Daybreak',
        swatches: ['#f4f3f8', '#7043aa', '#e4dbf2'],
    },
    {
        id: 'parchment',
        mode: 'light',
        name: 'Parchment',
        swatches: ['#f5f0e6', '#8c4a23', '#e3d3b8'],
    },
    {
        id: 'glacier',
        mode: 'light',
        name: 'Glacier',
        swatches: ['#eef3f6', '#1668a0', '#d3e4ee'],
    },
    {
        id: 'sage',
        mode: 'light',
        name: 'Sage',
        swatches: ['#f0f3ed', '#3e684f', '#dbe7d7'],
    },
    {
        id: 'rose-pine-dawn',
        mode: 'light',
        name: 'Rosé Pine Dawn',
        swatches: ['#faf4ed', '#705588', '#eee3ec'],
    },
    {
        id: 'porcelain',
        mode: 'light',
        name: 'Porcelain',
        swatches: ['#f5f5f4', '#404652', '#e2e4e8'],
    },
    {
        id: 'dracula',
        mode: 'dark',
        name: 'Dracula',
        swatches: ['#282a36', '#bd93f9', '#44405a'],
    },
    {
        id: 'nord',
        mode: 'dark',
        name: 'Nord',
        swatches: ['#2e3440', '#88c0d0', '#434c5e'],
    },
    {
        id: 'rose-pine',
        mode: 'dark',
        name: 'Rosé Pine',
        swatches: ['#191724', '#c4a7e7', '#40334d'],
    },
    {
        id: 'espresso',
        mode: 'dark',
        name: 'Espresso',
        swatches: ['#201914', '#e0a458', '#4a3826'],
    },
    {
        id: 'abyss',
        mode: 'dark',
        name: 'Abyss',
        swatches: ['#0e1620', '#5cc8e6', '#23405a'],
    },
    {
        id: 'gruvbox',
        mode: 'dark',
        name: 'Gruvbox',
        swatches: ['#282828', '#fabd2f', '#504536'],
    },
] as const;

export const DEFAULT_LIGHT_THEME: ThemeId = 'daybreak';
export const DEFAULT_DARK_THEME: ThemeId = 'dracula';

const isThemeId = (value: string | null): value is ThemeId =>
    THEMES.some((option) => option.id === value);

export function themeForMode(theme: ThemeId, mode: ThemeMode): ThemeId {
    const option = THEMES.find((candidate) => candidate.id === theme);

    if (option?.mode === mode) {
        return theme;
    }

    return mode === 'dark' ? DEFAULT_DARK_THEME : DEFAULT_LIGHT_THEME;
}

const resolveMode = (value: Appearance): ThemeMode => {
    if (value === 'system') {
        return prefersDark() ? 'dark' : 'light';
    }

    return value;
};

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    const mode = resolveMode(value);

    document.documentElement.classList.toggle('dark', mode === 'dark');
    document.documentElement.dataset.theme = themeForMode(
        getStoredTheme(),
        mode,
    );
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const APPEARANCES: readonly Appearance[] = ['light', 'dark', 'system'];

const isAppearance = (value: string | null): value is Appearance =>
    APPEARANCES.some((candidate) => candidate === value);

const getStoredAppearance = (): Appearance | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    const stored = localStorage.getItem('appearance');

    return isAppearance(stored) ? stored : null;
};

const getStoredTheme = (): ThemeId => {
    if (typeof window === 'undefined') {
        return DEFAULT_LIGHT_THEME;
    }

    const stored = localStorage.getItem('theme');

    return isThemeId(stored) ? stored : DEFAULT_LIGHT_THEME;
};

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system...
    const savedAppearance = getStoredAppearance();
    updateTheme(savedAppearance || 'system');

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

const appearance = ref<Appearance>('system');
const theme = ref<ThemeId>(DEFAULT_LIGHT_THEME);

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        const savedAppearance = getStoredAppearance();

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }

        // Adopt the stored preference untouched (system keeps cross-mode
        // ids so the preference survives OS changes) and realign the DOM.
        adoptStoredTheme();
        updateTheme(appearance.value);
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') {
            return prefersDark() ? 'dark' : 'light';
        }

        return appearance.value;
    });

    /**
     * Adopt the stored theme preference untouched. Under system the raw id
     * is preserved so a cross-mode preference survives OS changes; the DOM
     * always renders themeForMode(stored, resolvedMode) instead.
     */
    function adoptStoredTheme() {
        const stored = getStoredTheme();

        theme.value = stored;
        localStorage.setItem('theme', stored);
        setCookie('theme', stored);
    }

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        if (value === 'system') {
            adoptStoredTheme();
        } else {
            // An explicit mode cannot render a cross-mode palette, so fall
            // back to the mode default (mirrors AppearanceComposer coercion).
            const effective = themeForMode(getStoredTheme(), value);

            theme.value = effective;
            localStorage.setItem('theme', effective);
            setCookie('theme', effective);
        }

        updateTheme(value);
    }

    function selectTheme(value: ThemeId) {
        const option = THEMES.find((candidate) => candidate.id === value);

        if (!option) {
            return;
        }

        theme.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('theme', value);

        // Store in cookie for SSR...
        setCookie('theme', value);

        // A palette belongs to a mode, so selecting it switches to that
        // mode explicitly instead of staying on system.
        updateAppearance(option.mode);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
        theme,
        selectTheme,
    };
}
