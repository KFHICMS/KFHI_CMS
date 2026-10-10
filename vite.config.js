import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin/dashboard.css',
                'resources/css/child_officer/childdashboard.css',
                'resources/css/field_officer/fieldofficerdashboard.css',
                'resources/js/app.js',
                'resources/js/admin/dashboard.js',
                'resources/js/child_officer/childdashboard.js',
                'resources/js/field_officer/fieldofficerdashboard.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
