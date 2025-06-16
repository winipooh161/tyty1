<!-- JavaScript для работы с модальными окнами -->
<script>
/**
 * Класс для управления системой модальных окон
 */
class ModalPanelSystem {
    constructor() {
        this.activeModal = null;
        this.backdrop = document.getElementById('modal-backdrop');
        this.init();
        
        // QR сканер
        this.qrScanner = null;
        this.scannerInitialized = false;
        
        // Добавляем флаг для предотвращения повторных инициализаций
        this._isInitializingQrScanner = false;
        
        // Добавляем хранилище для отслеживания источника открытия модального окна
        this.modalSources = new Map();
    }
    
    init() {
        // Инициализация обработчиков событий для кнопок открытия/закрытия
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Обработчики для закрытия модальных окон
        document.querySelectorAll('[data-modal-close]').forEach(button => {
            button.addEventListener('click', () => this.closeModal());
        });
        
        // Закрытие при клике на фон (если не запрещено атрибутом)
        this.backdrop.addEventListener('click', () => {
            if (this.activeModal && !this.activeModal.hasAttribute('data-static')) {
                this.closeModal();
            }
        });
        
        // Обработка нажатия Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModal) {
                this.closeModal();
            }
        });
        
        // Отслеживаем клики на элементы с атрибутом data-modal-target
        document.addEventListener('click', (e) => {
            const modalTrigger = e.target.closest('[data-modal-target]');
            if (modalTrigger) {
                const modalId = modalTrigger.getAttribute('data-modal-target');
                const iconWrapper = modalTrigger.closest('.mb-icon-wrapper');
                
                if (iconWrapper) {
                    const iconId = iconWrapper.getAttribute('data-icon-id');
                    if (iconId) {
                        // Сохраняем связь между модальным окном и иконкой
                        this.modalSources.set(modalId, {
                            iconId: iconId,
                            element: iconWrapper
                        });
                    }
                }
            }
        });
    }
    
    /**
     * Открыть модальное окно по ID
     */
    openModal(modalId) {
        // Активно блокируем показ глобального спиннера перед открытием модального окна
        this.blockLoadingSpinner();
        
        // Если уже открыто другое модальное окно, закрываем его
        if (this.activeModal) {
            this.closeModal(true);
        }
        
        const modal = document.getElementById(modalId);
        if (!modal) return false;
        
        // Показываем фон и модальное окно
        this.backdrop.classList.add('show');
        modal.classList.add('show', 'animate-in');
        modal.style.display = 'flex';
        
        // Блокируем прокрутку страницы
        document.body.style.overflow = 'hidden';
        
        // Обновляем активное модальное окно
        this.activeModal = modal;
        
        // Вибрация для обратной связи (если поддерживается)
        if (navigator.vibrate && window.userHasInteractedWithPage && 
            !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            try {
                navigator.vibrate(30);
            } catch (error) {
                // Игнорируем ошибки vibrate API
            }
        }
        
        // Инициализируем QR сканер, если это соответствующее модальное окно
        if (modalId === 'qr-scanner-modal') {
            this.initQrScanner();
        }
        
        // Получаем информацию об источнике открытия модального окна
        let sourceIconId = null;
        
        // Проверяем наши внутренние источники
        if (this.modalSources && this.modalSources.has(modalId)) {
            sourceIconId = this.modalSources.get(modalId)?.iconId;
        }
        
        // Также проверяем наличие MobileNavPopup для альтернативного источника
        if (!sourceIconId && window.MobileNavWheelPicker && 
            window.MobileNavWheelPicker.popup &&
            window.MobileNavWheelPicker.popup.modalTriggers &&
            window.MobileNavWheelPicker.popup.modalTriggers.has(modalId)) {
            sourceIconId = window.MobileNavWheelPicker.popup.modalTriggers.get(modalId).iconId;
        }
        
        // Генерируем пользовательское событие после открытия модального окна
        const event = new CustomEvent('modal.opened', {
            detail: {
                modalId: modalId,
                sourceIconId: sourceIconId
            }
        });
        document.dispatchEvent(event);
        modal.dispatchEvent(new Event('show.modal-panel'));
        
        return true;
    }
    
    // Добавляем новый метод для активного блокирования спиннера
    blockLoadingSpinner() {
        if (!window.loadingSpinner) return;
        
        // Немедленно скрываем спиннер, если он показан
        window.loadingSpinner.forceHide();
        
        // Блокируем метод show на короткое время
        const originalShow = window.loadingSpinner.show;
        window.loadingSpinner.show = function() { 
            console.log('LoadingSpinner.show заблокирован для модального окна');
        };
        
        // Восстанавливаем метод show через небольшую задержку
        setTimeout(() => {
            window.loadingSpinner.show = originalShow;
        }, 1000);
        
        // Добавляем класс для маркировки текущего состояния
        document.body.classList.add('modal-active');
        
        // Удаляем маркер по закрытию модального окна
        setTimeout(() => {
            document.body.classList.remove('modal-active');
        }, 1000);
    }
    
    /**
     * Закрыть активное модальное окно
     */
    closeModal(immediate = false) {
        if (!this.activeModal) return;
        
        // Сохраняем ID модального окна для события
        const modalId = this.activeModal.id;
        
        // Остановка QR сканера, если был активен
        if (this.activeModal.id === 'qr-scanner-modal' && this.qrScanner) {
            this.stopQrScanner();
        }
        
        if (immediate) {
            // Немедленное закрытие без анимации
            this.backdrop.classList.remove('show');
            this.activeModal.classList.remove('show', 'animate-in');
            this.activeModal.style.display = 'none';
            document.body.style.overflow = '';
            this.activeModal = null;
            
            // Генерируем событие закрытия модального окна
            const event = new CustomEvent('modal.closed', {
                detail: {
                    modalId: modalId
                }
            });
            document.dispatchEvent(event);
        } else {
            // Закрытие с анимацией
            this.backdrop.classList.remove('show');
            this.activeModal.classList.remove('animate-in');
            this.activeModal.classList.add('animate-out');
            
            // Ждем завершения анимации
            setTimeout(() => {
                this.activeModal.classList.remove('show', 'animate-out');
                this.activeModal.style.display = 'none';
                document.body.style.overflow = '';
                this.activeModal = null;
                
                // Генерируем событие закрытия модального окна
                const event = new CustomEvent('modal.closed', {
                    detail: {
                        modalId: modalId
                    }
                });
                document.dispatchEvent(event);
            }, 300);
        }
    }
    
    // Методы для работы с QR сканером
    initQrScanner() {
        // Предотвращаем повторную инициализацию
        if (this._isInitializingQrScanner) {
            console.warn('Инициализация QR сканера уже запущена, пропускаем');
            return;
        }
        
        this._isInitializingQrScanner = true;
        
        // Сначала полностью останавливаем текущий сканер, если он есть
        this.stopQrScanner();
        
        // Добавляем задержку перед новой инициализацией
        setTimeout(() => {
            if (typeof initQrScannerModule === 'function') {
                try {
                    initQrScannerModule(this);
                } catch (e) {
                    console.error('Ошибка при инициализации QR сканера через модальную систему:', e);
                }
            } else {
                console.error('Функция initQrScannerModule не определена');
            }
            
            this._isInitializingQrScanner = false;
        }, 300);
    }
    
    stopQrScanner() {
        if (typeof stopQrScannerModule === 'function') {
            try {
                stopQrScannerModule(this);
            } catch (e) {
                console.warn('Ошибка при остановке QR сканера через модальную систему:', e);
            }
        }
    }
}

// Создаем глобальный экземпляр системы модальных окон
window.modalPanel = new ModalPanelSystem();

// Универсальная функция для открытия модальных окон
window.openModalPanel = function(modalId) {
    if (window.modalPanel) {
        return window.modalPanel.openModal(modalId);
    }
    return false;
};

// Связываем функцию открытия QR-сканера с существующей функцией
window.openQrScannerModal = function(iconElement) {
    // Если передан элемент иконки, сохраняем связь
    if (iconElement && iconElement.getAttribute) {
        const iconId = iconElement.getAttribute('data-icon-id');
        if (iconId && window.modalPanel) {
            window.modalPanel.modalSources.set('qr-scanner-modal', {
                iconId: iconId,
                element: iconElement
            });
        }
    }
    return openModalPanel('qr-scanner-modal');
};
</script>
