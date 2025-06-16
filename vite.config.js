import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/modal-styles.css',
                'resources/css/templates.css',
                'resources/css/mobile-nav.css',
                'resources/css/mobile-nav-hint.css', // Добавляем новый CSS-файл
                'resources/js/app.js',
                'resources/js/mobile-nav-wheel-picker.js',
                'resources/js/loading-spinner.js',
                'resources/js/register-sw.js' // Добавляем скрипт для регистрации Service Worker
                       
            ],
            refresh: true,
        }),
    ],
    server: {
        // Настройка CORS для разработки
        cors: {
            origin: ['https://tyty', 'http://localhost', 'http://127.0.0.1'],
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            credentials: true
        },
        // Настройка для доступа по сети
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
    },
});
