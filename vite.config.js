import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js', 
                'resources/js/bellaroma.js' // <-- NUEVO SCRIPT COMPILABLE
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', 
        cors: true, // <-- ESTA ES LA LLAVE MAESTRA PARA MATAR EL ERROR
        hmr: {
            host: '192.168.1.66', 
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});