import { MobileNavCore } from './MobileNavCore.js';
import { MobileNavEvents } from './MobileNavEvents.js';
import { MobileNavScroll } from './MobileNavScroll.js';
import { MobileNavPopup } from './MobileNavPopup.js';
import { MobileNavStorage } from './MobileNavStorage.js';
import { MobileNavUtils } from './MobileNavUtils.js';

class MobileNavWheelPicker {
    constructor() {
        this.core = new MobileNavCore();
        this.storage = new MobileNavStorage();
        this.scroll = new MobileNavScroll(this.core);
        this.popup = new MobileNavPopup(this.core);
        this.utils = new MobileNavUtils(this.core, this.scroll);
        this.events = new MobileNavEvents(this.core, this.scroll, this.popup);
        
        // Регистрируем в глобальной области для доступа из других скриптов
        window.MobileNavWheelPicker = this;
        
        // Инициализируем интеграцию с модальными окнами
        this.initModalIntegration();
    }

    initModalIntegration() {
        // Обработчик открытия модальных окон - интеграция с модальной системой
        document.addEventListener('modal.opened', (event) => {
            const modalId = event.detail?.modalId;
            const sourceIconId = event.detail?.sourceIconId;
            
            if (modalId && sourceIconId) {
                console.log(`MobileNav: Модальное окно ${modalId} открыто из иконки ${sourceIconId}`);
                
                // Добавляем класс активной иконки для визуальной связи
                const iconElement = document.querySelector(`[data-icon-id="${sourceIconId}"]`);
                if (iconElement) {
                    iconElement.classList.add('modal-source-active');
                }
            }
        });
        
        // Обработчик закрытия модальных окон
        document.addEventListener('modal.closed', (event) => {
            // Удаляем класс активной иконки со всех элементов
            document.querySelectorAll('.modal-source-active').forEach(element => {
                element.classList.remove('modal-source-active');
            });
        });
    }
}

// Создаем экземпляр после загрузки DOM
document.addEventListener('DOMContentLoaded', () => {
    // Инициализируем с небольшой задержкой для гарантии загрузки всех элементов
    setTimeout(() => new MobileNavWheelPicker(), 100);
});

export default MobileNavWheelPicker;
