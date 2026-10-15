import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        host: '127.0.0.1',
    },
    plugins: [
        laravel({
            input: ['resources/js/app.jsx', 'resources/js/admin.js'],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
    ],
});
