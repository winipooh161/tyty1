import './bootstrap';

// Импортируем Bootstrap JavaScript
import * as bootstrap from 'bootstrap';

// Импортируем спиннер загрузки
import './loading-spinner.js';

// Импортируем файл навигации с колесом выбора
import './mobile-nav-wheel-picker.js';

// Импортируем менеджер кэшей
import './cache-manager.js';


// Проверяем, не инициализирован ли уже Bootstrap
if (!window.bootstrap) {
    window.bootstrap = bootstrap;
}
