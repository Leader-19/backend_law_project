import { wayfinder } from '@laravel/vite-plugin-wayfinder'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { VitePWA } from 'vite-plugin-pwa'
import { defineConfig } from 'vite'
import path from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),

        tailwindcss(),

        wayfinder({
            formVariants: true,
        }),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),

        VitePWA({
            registerType: 'autoUpdate',

            filename: 'sw.js',

            base: '/',

            scope: '/',

            includeAssets: [
                'favicon.ico',
                'favicon.svg',
                'apple-touch-icon.png',
                'logo.jpg',
            ],

            workbox: {
                navigateFallback: undefined,

                navigateFallbackDenylist: [
                    /^\/api\//,
                    /^\/backup\//,
                    /^\/storage\//,
                ],
            },

            manifest: {
                name: 'SPRITUP',
                short_name: 'SPRITUP',
                description: 'SPRITUP Application',

                theme_color: '#ffffff',
                background_color: '#ffffff',

                display: 'standalone',

                start_url: '/',
                scope: '/',

                icons: [
                    {
                        src: '/logo.jpg',
                        sizes: '1024x1024',
                        type: 'image/jpeg',
                        purpose: 'any maskable',
                    },
                ],
            },
        }),
    ],

    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },

    server: {
        watch: {
            ignored: [
                '**/vendor/**',
                '**/node_modules/**',
                '**/.git/**',
                '**/storage/framework/**',
            ],
        },
    },
})
