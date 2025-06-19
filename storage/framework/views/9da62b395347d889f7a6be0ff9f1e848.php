<!-- JavaScript для работы с модальными окнами -->
<script>
/**
 * Класс для управления системой модальных окон
 */
class ModalPanelSystem {
    constructor() {
        this.activeModal = null;
        this.backdrop = document.getElementById('modal-backdrop');
        this.modalSources = new Map();
        
        // Переменные для управления скроллом
        this.scrollBlocked = false;
        
        // Флаги для предотвращения конфликтов
        this.isClosing = false;
        
        this.init();
    }
    
    init() {
        // Инициализация обработчиков событий для кнопок открытия/закрытия
        this.setupEventListeners();
        
        // Интеграция с мобильной навигацией
        this.setupMobileNavEventListeners();
        
        // Устанавливаем глобальные флаги состояния
        window.modalClosingInProgress = false;
        window.qrScannerBlockOpen = false;
        window.lastModalClosed = 0;
        window.modalOpeningInProgress = false;
    }
    
    setupMobileNavEventListeners() {
        // Безопасно добавляем слушатели после загрузки DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initMobileNavEvents();
            });
        } else {
            setTimeout(() => this.initMobileNavEvents(), 0);
        }
    }
    
    initMobileNavEvents() {
        // Находим все иконки с атрибутом data-modal
        const modalTriggers = document.querySelectorAll('[data-icon-id][data-modal="true"]');
        
        modalTriggers.forEach(trigger => {
            // Убираем старые обработчики, чтобы избежать дублирования
            const iconId = trigger.getAttribute('data-icon-id');
            const modalId = trigger.getAttribute('data-modal-target');
            
            if (iconId && modalId) {
                // Сохраняем связь модального окна с иконкой
                this.modalSources.set(modalId, {
                    iconId: iconId,
                    element: trigger
                });
                
                // Для специальных иконок (например, QR-сканер) добавляем особую обработку
                if (iconId === 'qr-scanner') {
                    this.setupQrScannerHandlers(trigger, modalId);
                }
            }
        });
        
        // Также обрабатываем ссылки внутри иконок
        document.querySelectorAll('[data-icon-id] .mb-nav-link[href="#"][data-modal-target]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const modalId = link.getAttribute('data-modal-target');
                if (modalId) {
                    this.openModal(modalId);
                }
            });
        });
    }
    
    setupQrScannerHandlers(trigger, modalId) {
        // Особая обработка для QR-сканера
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Используем внешний контроллер, если доступен
            if (window.qrScannerController && typeof window.qrScannerController.open === 'function') {
                window.qrScannerController.open(trigger);
            } else {
                this.openModal(modalId);
            }
        });
        
        // Обрабатываем клик на самой ссылке 
        const qrLink = trigger.querySelector('.mb-nav-link');
        if (qrLink) {
            qrLink.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if (window.qrScannerController && typeof window.qrScannerController.open === 'function') {
                    window.qrScannerController.open(trigger);
                } else {
                    this.openModal(modalId);
                }
            });
        }
    }
    
    setupEventListeners() {
        // Обработчики для закрытия модальных окон
        document.querySelectorAll('[data-modal-close]').forEach(button => {
            button.addEventListener('click', () => this.closeModal());
        });
        
        // Закрытие при клике на фон (если не запрещено атрибутом)
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => {
                if (this.activeModal && !this.activeModal.hasAttribute('data-static')) {
                    this.closeModal();
                }
            });
        }
        
        // Обработка нажатия Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModal && !this.activeModal.hasAttribute('data-static')) {
                this.closeModal();
            }
        });
        
        // Отслеживаем клики на элементы с атрибутом data-modal-target
        document.addEventListener('click', (e) => {
            const modalTrigger = e.target.closest('[data-modal-target]');
            if (modalTrigger) {
                e.preventDefault(); 
                e.stopPropagation();
                
                const modalId = modalTrigger.getAttribute('data-modal-target');
                
                // Проверяем, не вызывалось ли уже открытие модального окна
                if (window.modalOpeningInProgress) {
                    return false;
                }
                
                // Предотвращаем множественные клики
                window.modalOpeningInProgress = true;
                setTimeout(() => {
                    window.modalOpeningInProgress = false;
                }, 500);
                
                // Сохраняем информацию об источнике модального окна
                const iconWrapper = modalTrigger.closest('.mb-icon-wrapper');
                if (iconWrapper) {
                    const iconId = iconWrapper.getAttribute('data-icon-id');
                    if (iconId) {
                        this.modalSources.set(modalId, {
                            iconId: iconId,
                            element: iconWrapper
                        });
                    }
                }
                
                // Открываем модальное окно
                this.openModal(modalId);
            }
        });
    }
    
    /**
     * Открыть модальное окно по ID
     */
    openModal(modalId) {
        // Проверяем глобальный флаг закрытия
        if (window.modalClosingInProgress || this.isClosing || 
            (window.lastModalClosed && (Date.now() - window.lastModalClosed) < 1000)) {
            return false;
        }
        
        // Специальная проверка для QR-сканера
        if (modalId === 'qrScannerModal' && window.qrScannerBlockOpen) {
            return false;
        }
        
        // Активно блокируем показ глобального спиннера перед открытием модального окна
        this.blockLoadingSpinner();
        
        // Если уже открыто другое модальное окно, закрываем его
        if (this.activeModal) {
            this.closeModal(true);
        }
        
        const modal = document.getElementById(modalId);
        if (!modal) return false;
        
        // Блокируем скролл body
        this.blockBodyScroll();
        
        // Показываем фон и модальное окно
        if (this.backdrop) {
            this.backdrop.classList.add('show');
        }
        
        modal.classList.add('show', 'animate-in');
        modal.style.display = 'flex';
        
        // Обновляем активное модальное окно
        this.activeModal = modal;
        
        // Вибрация для обратной связи (если поддерживается)
        if (navigator.vibrate && window.userHasInteractedWithPage && 
            !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            try {
                navigator.vibrate(30);
            } catch (error) {
                // Игнорируем ошибки вибрации
            }
        }
        
        // Инициализация QR сканера
        if (modalId === 'qrScannerModal' && window.qrScannerController) {
            // Инициализация через внешний контроллер
        }
        
        // Получаем информацию об источнике открытия
        let sourceIconId = null;
        if (this.modalSources.has(modalId)) {
            sourceIconId = this.modalSources.get(modalId)?.iconId;
        }
        
        // Генерируем события открытия модального окна
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
    
    // Метод для блокировки глобального спиннера
    blockLoadingSpinner() {
        if (!window.loadingSpinner) return;
        
        // Скрываем спиннер, если он показан
        window.loadingSpinner.forceHide();
        
        // Блокируем метод show на короткое время
        const originalShow = window.loadingSpinner.show;
        window.loadingSpinner.show = function() { 
            console.log('LoadingSpinner.show заблокирован');
        };
        
        // Восстанавливаем метод через небольшую задержку
        setTimeout(() => {
            window.loadingSpinner.show = originalShow;
        }, 800);
        
        // Добавляем маркер состояния
        document.body.classList.add('modal-active');
        setTimeout(() => {
            document.body.classList.remove('modal-active');
        }, 800);
    }
    
    /**
     * Закрыть активное модальное окно
     */
    closeModal(immediate = false) {
        if (!this.activeModal || this.isClosing) return;
        
        // Устанавливаем флаги закрытия
        this.isClosing = true;
        window.modalClosingInProgress = true;
        
        // Сохраняем ID модального окна
        const modalId = this.activeModal.id;
        
        // Фиксируем время закрытия
        window.lastModalClosed = Date.now();
        
        // Специальная обработка для sub-profile-modal
        if (modalId === 'sub-profile-modal') {
            // Добавляем небольшую задержку перед закрытием
            // чтобы успели отработать все необходимые события
            setTimeout(() => {
                this.processModalClose(modalId, immediate);
            }, 100);
            return;
        }
        
        // Стандартная обработка для всех остальных модальных окон
        this.processModalClose(modalId, immediate);
    }
    
    // Добавим новый метод для общего кода закрытия
    processModalClose(modalId, immediate = false) {
        // Остановка QR сканера
        if (modalId === 'qrScannerModal') {
            // Защита от повторного открытия
            window.qrScannerBlockOpen = true;
            setTimeout(() => {
                window.qrScannerBlockOpen = false;
            }, 2000);
            
            // Остановка через контроллер
            if (window.qrScannerController) {
                try {
                    window.qrScannerController.stopScanner();
                } catch (e) {
                    console.error('Ошибка при остановке QR сканера', e);
                }
            }
        }
        
        // Проверка на блокатор закрытия
        if (!immediate && this.activeModal && this.activeModal.hasAttribute('data-static')) {
            immediate = true;
        }
        
        if (immediate) {
            // Немедленное закрытие
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
            
            this.activeModal.classList.remove('show', 'animate-in');
            this.activeModal.style.display = 'none';
            
            // Разблокируем скролл
            this.unblockBodyScroll();
            
            // Генерируем события
            this.triggerClosedEvent(modalId);
            
            this.activeModal = null;
            
            // Сбрасываем флаги
            setTimeout(() => {
                this.isClosing = false;
                window.modalClosingInProgress = false;
            }, 500);
        } else {
            // Закрытие с анимацией
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
            
            this.activeModal.classList.remove('animate-in');
            this.activeModal.classList.add('animate-out');
            
            // Ждем завершения анимации
            setTimeout(() => {
                if (this.activeModal) {
                    this.activeModal.classList.remove('show', 'animate-out');
                    this.activeModal.style.display = 'none';
                    
                    // Разблокируем скролл
                    this.unblockBodyScroll();
                    
                    // Генерируем события
                    this.triggerClosedEvent(modalId);
                    
                    this.activeModal = null;
                }
                
                // Сбрасываем флаги с большей задержкой
                setTimeout(() => {
                    this.isClosing = false;
                    window.modalClosingInProgress = false;
                }, 500);
            }, 300);
        }
    }
    
    // Отдельный метод для генерации события закрытия
    triggerClosedEvent(modalId) {
        const event = new CustomEvent('modal.closed', {
            detail: {
                modalId: modalId
            }
        });
        document.dispatchEvent(event);
    }
    
    // Методы для управления скроллом через общие обработчики
    blockBodyScroll() {
        if (this.scrollBlocked) return;
        
        if (window.mobileNavUtils && typeof window.mobileNavUtils.blockBodyScroll === 'function') {
            window.mobileNavUtils.blockBodyScroll();
        } else {
            // Резервная реализация
            const scrollY = window.pageYOffset || document.documentElement.scrollTop;
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.width = '100%';
            document.body.classList.add('modal-scroll-blocked');
        }
        
        this.scrollBlocked = true;
    }
    
    unblockBodyScroll() {
        if (!this.scrollBlocked) return;
        
        if (window.mobileNavUtils && typeof window.mobileNavUtils.unblockBodyScroll === 'function') {
            window.mobileNavUtils.unblockBodyScroll();
        } else {
            // Резервная реализация
            const scrollY = parseInt(document.body.style.top || '0', 10) * -1;
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.classList.remove('modal-scroll-blocked');
            window.scrollTo(0, scrollY);
        }
        
        this.scrollBlocked = false;
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

// Универсальная функция для закрытия модальных окон
window.closeModalPanel = function(modalId) {
    if (window.modalPanel) {
        return window.modalPanel.closeModal();
    }
    return false;
};

// Устанавливаем функцию для открытия QR-сканера
window.openQrScannerModal = function(iconElement) {
    // Проверяем блокировки
    if (window.modalClosingInProgress || window.qrScannerBlockOpen || 
        (window.lastModalClosed && (Date.now() - window.lastModalClosed) < 1000)) {
        return false;
    }
    
    // Предпочитаем использовать специализированный контроллер
    if (window.qrScannerController && typeof window.qrScannerController.open === 'function') {
        return window.qrScannerController.open(iconElement);
    }
    
    // Запасной вариант
    return openModalPanel('qrScannerModal');
};
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-system.blade.php ENDPATH**/ ?>