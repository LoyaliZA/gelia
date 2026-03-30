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
                'resources/js/bellaroma.js', // <-- NUEVO SCRIPT COMPILABLE
                'resources/js/aromas.js', // <-- NUEVO SCRIPT COMPILABLE
                'resources/js/aromas/clientes.js', // <-- NUEVO SCRIPT COMPILABLE
                'resources/js/aromas/gastos.js', // <-- NUEVO SCRIPT COMPILABLE
                'resources/js/aromas/transacciones.js', // <-- NUEVO SCRIPT COMPILABLE
                'resources/js/aromas/listados.js', // <-- NUEVO SCRIPT COMPILABLE
                'resources/js/aromas/avisos.js', // <-- NUEVO SCRIPT COMPILABLE
                'resources/js/woocommerce.js', // <-- NUEVO SCRIPT COMPILABLE PARA PROCESAR CSV DE WOOCOMMERCE
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', 
        cors: true, // <-- Evitamos que haya un error de permisos al hacer fetch desde el frontend
        hmr: {
            host: '100.75.11.59', 
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});