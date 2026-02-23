import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'plugins/DixlaseLegal/resources/src/js/app.js',
                'plugins/DixlaseLegal/resources/src/css/style.css',
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'plugins/DixlaseLegal/resources/assets',
    },
});