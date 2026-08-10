import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import legacy from '@vitejs/plugin-legacy';
import svgLoader from 'vite-svg-loader';

export default defineConfig({
    build: {
        // no target option: managed by @vitejs/plugin-legacy
        rollupOptions: {
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: 'js/[name].[ext]',
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js'
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
        legacy({
            modernTargets: 'chrome >= 71',
            modernPolyfills: true,
            renderLegacyChunks: false,
        }),
        svgLoader(),
    ],
});
