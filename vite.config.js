import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';

export default defineConfig({
    plugins: [
        symfonyPlugin(),
    ],
    build: {
        // Webfonty nikdy neinlinovat — Vite default 4 KB inlinoval malé woff2
        // (např. jetbrains-mono-cyrillic-ext.woff2 ~1.6 KB) jako data: URI,
        // což porušovalo přísné CSP `font-src 'self'`.
        assetsInlineLimit: (filePath) =>
            filePath.endsWith('.woff2') || filePath.endsWith('.woff') ? 0 : 4096,
        rollupOptions: {
            input: {
                app: './assets/app.js',
            },
        },
    },
});
