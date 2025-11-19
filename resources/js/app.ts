import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { createPinia } from 'pinia';
import Echo from '@ably/laravel-echo';
import * as Ably from 'ably';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Initialize Ably and Laravel Echo for real-time broadcasting
window.Ably = Ably;

if (import.meta.env.VITE_ABLY_KEY) {
    // Initialize Ably and make it globally available
    window.Ably = Ably;

    // Initialize Laravel Echo with Ably
    // The @ably/laravel-echo package has built-in Ably support
    window.Echo = new Echo({
        broadcaster: 'ably',
        key: import.meta.env.VITE_ABLY_KEY,
        encrypted: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        },
    });

    // Log connection status for debugging
    if (window.Echo.connector?.ably) {
        window.Echo.connector.ably.connection.on((stateChange: any) => {
            console.log('Ably connection state:', stateChange.current);
            if (stateChange.current === 'connected') {
                console.log('✅ Ably connected successfully');
            } else if (stateChange.current === 'disconnected') {
                console.warn('⚠️ Ably disconnected:', stateChange.reason);
            }
        });
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(createPinia());
        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
