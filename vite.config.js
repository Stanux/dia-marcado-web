import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    build: {
        emptyOutDir: false,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/filament/admin/theme.css',
                'resources/js/app.js',
                'resources/js/media-gallery.js'
            ],
            refresh: true,
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
});
