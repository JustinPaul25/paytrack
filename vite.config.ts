import path from 'path';
import { fileURLToPath } from 'url';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    optimizeDeps: {
        include: ['sweetalert2'],
    },
    resolve: {
        alias: {
            // Force ESM entry so Rollup can resolve default export (fixes traceVariable on deploy)
            sweetalert2: path.resolve(__dirname, 'node_modules/sweetalert2/dist/sweetalert2.esm.all.js'),
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
