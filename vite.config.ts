import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
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
