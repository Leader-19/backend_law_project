import '../css/app.css'

import { createInertiaApp, router } from '@inertiajs/vue3'
// import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import { initializeTheme } from './composables/useAppearance'

import { ZiggyVue } from 'ziggy-js'
import { Ziggy } from './ziggy'

import { registerSW } from 'virtual:pwa-register'

// Pinia
import { createPinia } from 'pinia'

// Loading store
import { useLoadingStore } from './pages/stores/LoadingPage'

const appName = import.meta.env.VITE_APP_NAME || 'SPRITUP'

/*
|--------------------------------------------------------------------------
| Service Worker
|--------------------------------------------------------------------------
|
| vite-plugin-pwa handles the service worker registration.
| Do NOT manually call navigator.serviceWorker.register().
|
*/
registerSW({
    immediate: true,
})

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        const pinia = createPinia()

        const vueApp = createApp({
            render: () => h(App, props),
        })

        vueApp
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, Ziggy)
            .mount(el)

        const loading = useLoadingStore()

        router.on('start', () => {
            loading.start()
        })

        router.on('finish', () => {
            loading.stop()
        })
    },

    progress: {
        color: '#4B5563',
    },
})

initializeTheme()
