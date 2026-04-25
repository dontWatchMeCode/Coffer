import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
            case name.startsWith('teams/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);

        // Forward Vue errors to Pest browser's jsErrors array (browser tests only).
        // @ts-expect-error Pest browser plugin injects __pestBrowser at runtime.
        if (typeof window.__pestBrowser !== 'undefined') {
            app.config.errorHandler = (err) => {
                // @ts-expect-error Pest browser plugin injects __pestBrowser at runtime.
                if (window.__pestBrowser?.jsErrors) {
                    // @ts-expect-error Pest browser plugin injects __pestBrowser at runtime.
                    window.__pestBrowser.jsErrors.push({
                        message: err instanceof Error ? err.message : String(err),
                        filename: 'vue-error-handler',
                        lineno: 0,
                        colno: 0,
                    });
                }
            };
        }

        // @ts-expect-error Inertia passes el as HTMLElement but createApp mount expects a stricter type.
        app.mount(el);

        return app;
    },
});

// This will set light / dark mode on page load...
initializeTheme();
