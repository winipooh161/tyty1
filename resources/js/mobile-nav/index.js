import { MobileNavCore } from './MobileNavCore.js';
import { MobileNavEvents } from './MobileNavEvents.js';
import { MobileNavPopup } from './MobileNavPopup.js';
import { MobileNavScroll } from './MobileNavScroll.js';
import { MobileNavUtils } from './MobileNavUtils.js';
import { MobileNavStorage } from './MobileNavStorage.js';

class MobileNavWheelPicker {
    constructor() {
        // Создаем экземпляр ядра
        this.core = new MobileNavCore();
        
        // Инициализируем управление скроллом
        this.scroll = new MobileNavScroll(this.core);
        
        // Инициализируем утилитные функции
        this.utils = new MobileNavUtils(this.core, this.scroll);
        
        // Инициализируем всплывающие меню
        this.popup = new MobileNavPopup(this.core);
        
        // Инициализируем обработчики событий
        this.events = new MobileNavEvents(this.core, this.scroll, this.popup);
        
        // Инициализируем хранилище
        this.storage = new MobileNavStorage();
        
        // Запускаем инициализацию после загрузки страницы
        this.init();
    }

    init() {
        // Используем DOMContentLoaded для безопасной инициализации
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.postInit();
            });
        } else {
            // DOM уже загружен
            this.postInit();
        }
    }

    postInit() {
        // Задержка для гарантированной загрузки DOM
        setTimeout(() => {
            // Запускаем дополнительные настройки после инициализации
            this.core.showNavigation();
            
            // Настраиваем слушатели событий для скрытия/показа навигации
            this.scroll.setupPageScrollListener();
            
            // Настраиваем наблюдатель за видимостью элементов
            this.utils.setupIntersectionObserver();
        }, 400);
    }
}

// Экспортируем класс для использования в других модулях
export default MobileNavWheelPicker;

// Создаем глобальный экземпляр
if (typeof window !== 'undefined') {
    window.MobileNavWheelPicker = new MobileNavWheelPicker();
}
