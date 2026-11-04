import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ command }) => ({
    // Relative paths let lazy-loaded chunks work when the site runs in a subfolder
    base: command === 'build' ? './' : undefined,
    plugins: [
        laravel({
            input: ['resources/css/admin.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                // Keep paths like /images/x.jpg pointing at public/
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                compilerOptions: {
                    // Page text relies on whitespace between inline tags
                    whitespace: 'preserve',
                    // Legacy tag from the original template, not a Vue component
                    isCustomElement: (tag) => tag === 'center',
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5200,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        watch: {
            // File events from Windows don't reach the container
            usePolling: true,
            interval: 1000,
            // Polling the Windows mount is slow, so only watch source folders
            ignored: (path) => /\/(vendor|storage|public|bootstrap|node_modules|_original-site|\.shots|tests|database|config|app|docker)(\/|$)/.test(path),
        },
    },
}));
