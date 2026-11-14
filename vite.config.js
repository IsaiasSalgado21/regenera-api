import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/map/map-entry.js',
                'resources/js/flora-detail-map.js',
                'resources/js/dashboard/dashboard.js',
                'resources/js/learning.js',
                'resources/js/flora-admin.js',
                'resources/js/dashboard/cuestionarios-admin.js',
                'resources/js/personal-volunteers.js',
                'resources/js/voluntariado-detail.js',
                'resources/js/asistencia.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
