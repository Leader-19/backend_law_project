import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
     theme: {
    extend: {
      fontFamily: {
        battambang: ['Battambang', 'sans-serif'],

        // OPTIONAL: set as default
        sans: ['Battambang', 'sans-serif'],
      },
    },
  },
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
            includeAssets: ['favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'logo.jpg'],
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
                        sizes: '192x192',
                        type: 'image/jpeg',
                    },
                    {
                        src: '/logo.jpg',
                        sizes: '512x512',
                        type: 'image/jpeg',
                    },
                    {
                        src: '/logo.jpg',
                        sizes: '512x512',
                        type: 'image/jpeg',
                        purpose: 'maskable',
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

    // Laravel/Tailwind can discover files under `vendor/`. Do not ask the
    // development server to create an inotify watcher for every dependency.
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
});
