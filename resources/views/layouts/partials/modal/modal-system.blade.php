<!-- JavaScript для работы с модальными окнами -->
<script>
/**
 * Класс для управления системой модальных окон
 */
class ModalPanelSystem {
    constructor() {
        // Базовое состояние
        this.activeModal = null;
        this.backdrop = document.getElementById('modal-backdrop');
        this.modalSources = new Map();
        
        // Единое управление состоянием с упрощенным набором флагов
        this.state = {
            isTransitioning: false,  // Флаг переходного состояния (открытие/закрытие в процессе)
            scrollBlocked: false,    // Флаг блокировки скролла
            lastActionTime: 0        // Метка времени последнего действия для дебаунсинга
        };
        
        // Набор таймаутов для очистки
        this.timeouts = new Set();
        
        this.init();
    }
    
    init() {
        // Инициализация обработчиков событий для кнопок открытия/закрытия
        this.setupEventListeners();
        
        // Интеграция с мобильной навигацией
        this.setupMobileNavEventListeners();
        
        // Установка глобальных обработчиков
        this.setupGlobalHandlers();
        
        console.log('ModalPanelSystem: инициализирован');
    }
    
    /**
     * Установка обработчиков глобальных событий
     */
    setupGlobalHandlers() {
        // Отслеживание истории браузера для корректной работы с кнопкой "назад"
        window.addEventListener('popstate', () => {
            if (this.activeModal) {
                this.closeModal(true);
            }
        });
        
        // Обработка событий касания для улучшения отзывчивости на мобильных
        document.addEventListener('touchstart', () => {
            window.userHasInteractedWithPage = true;
        }, { passive: true, once: true });
    }
    
    setupMobileNavEventListeners() {
        // Безопасная инициализация после загрузки DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initMobileNavEvents());
        } else {
            this.initMobileNavEvents();
        }
    }
    
    initMobileNavEvents() {
        // Находим все иконки с атрибутом data-modal
        const modalTriggers = document.querySelectorAll('[data-icon-id][data-modal="true"]');
        
        modalTriggers.forEach(trigger => {
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
        
        // Также обрабатываем ссылки внутри иконок с делегированием событий
        document.addEventListener('click', (e) => {
            // Проверяем, является ли цель или родитель целью ссылкой с модальным атрибутом
            const link = e.target.closest('[data-icon-id] .mb-nav-link[data-modal-target]');
            if (link) {
                e.preventDefault();
                e.stopPropagation();
                
                const modalId = link.getAttribute('data-modal-target');
                if (modalId) {
                    this.openModal(modalId);
                }
            }
        });
    }
    
    setupQrScannerHandlers(trigger, modalId) {
        // Используем делегирование событий для более эффективной обработки
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Используем внешний контроллер, если доступен
            if (window.qrScannerController && typeof window.qrScannerController.open === 'function') {
                window.qrScannerController.open(e);
            } else {
                this.openModal(modalId);
            }
        });
    }
    
    setupEventListeners() {
        // Делегирование событий для закрытия модальных окон
        document.addEventListener('click', (e) => {
            // Обрабатываем клик на элементы закрытия
            if (e.target.hasAttribute('data-modal-close') || e.target.closest('[data-modal-close]')) {
                e.preventDefault();
                this.closeModal();
            }
            
            // Обрабатываем клик на фон (если модальное окно не статическое)
            if (e.target === this.backdrop && this.activeModal && !this.activeModal.hasAttribute('data-static')) {
                this.closeModal();
            }
            
            // Обрабатываем клик на триггеры модальных окон
            const modalTrigger = e.target.closest('[data-modal-target]');
            if (modalTrigger) {
                e.preventDefault();
                e.stopPropagation();
                
                // Проверка дебаунса для предотвращения множественных открытий
                const now = Date.now();
                if (now - this.state.lastActionTime < 500) return;
                this.state.lastActionTime = now;
                
                const modalId = modalTrigger.getAttribute('data-modal-target');
                
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
        
        // Обработка нажатия Escape - оптимизированная версия
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && 
                this.activeModal && 
                !this.activeModal.hasAttribute('data-static') &&
                !this.state.isTransitioning) {
                this.closeModal();
            }
        });
    }
    
    /**
     * Открыть модальное окно по ID - оптимизированная версия с защитой от гонки состояний
     */
    openModal(modalId) {
        // Проверка на защиту от дребезга и состояния перехода
        if (this.state.isTransitioning) {
            return false;
        }
        
        // Устанавливаем состояние перехода
        this.state.isTransitioning = true;
        
        // Блокируем глобальный спиннер
        this.blockLoadingSpinner();
        
        // Если уже открыто другое модальное окно, закрываем его немедленно
        if (this.activeModal) {
            this.closeModal(true);
        }
        
        // Получаем модальное окно и проверяем его наличие
        const modal = document.getElementById(modalId);
        if (!modal) {
            this.state.isTransitioning = false;
            return false;
        }
        
        // Блокируем скролл страницы
        this.blockBodyScroll();
        
        // Показываем фон
        if (this.backdrop) {
            this.backdrop.classList.add('show');
        }
        
        // Инициализируем модальное окно с одним reflow для повышения производительности
        modal.style.display = 'flex';
        
        // Форсируем reflow для правильной анимации
        void modal.offsetWidth;
        
        // Добавляем классы для анимации появления
        modal.classList.add('show', 'animate-in');
        
        // Обновляем активное модальное окно
        this.activeModal = modal;
        
        // Тактильная обратная связь, если доступна и пользователь взаимодействовал
        this.provideTactileFeedback(30);
        
        // Получаем информацию об источнике открытия
        let sourceIconId = null;
        if (this.modalSources.has(modalId)) {
            sourceIconId = this.modalSources.get(modalId)?.iconId;
        }
        
        // Генерируем события открытия модального окна
        this.triggerModalEvent('modal.opened', { modalId, sourceIconId });
        modal.dispatchEvent(new Event('show.modal-panel'));
        
        // Сбрасываем флаг переходного состояния после завершения анимации
        this.scheduleTask(() => {
            this.state.isTransitioning = false;
        }, 300);
        
        return true;
    }
    
    /**
     * Блокирует глобальный спиннер загрузки для улучшения UX
     */
    blockLoadingSpinner() {
        if (!window.loadingSpinner) return;
        
        // Скрываем спиннер, если он показан
        if (window.loadingSpinner.forceHide) {
            window.loadingSpinner.forceHide();
        }
        
        // Блокируем метод show на короткое время
        const originalShow = window.loadingSpinner.show;
        window.loadingSpinner.show = function() {}; 
        
        // Восстанавливаем метод через небольшую задержку
        this.scheduleTask(() => {
            window.loadingSpinner.show = originalShow;
        }, 800);
    }
    
    /**
     * Предоставляет тактильную обратную связь, если это возможно
     */
    provideTactileFeedback(duration = 20) {
        // Проверяем наличие флага взаимодействия пользователя
        if (navigator.vibrate && 
            window.userHasInteractedWithPage === true && 
            !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            try {
                navigator.vibrate(Math.min(duration, 20)); // Ограничиваем максимальную длительность
            } catch (error) {
                // Игнорируем ошибки вибрации
            }
        }
    }
    
    /**
     * Закрыть активное модальное окно - оптимизированная версия
     */
    closeModal(immediate = false) {
        // Проверяем наличие активного модального окна и статус перехода
        if (!this.activeModal || (this.state.isTransitioning && !immediate)) {
            return false;
        }
        
        // Устанавливаем состояние перехода
        this.state.isTransitioning = true;
        
        // Сохраняем ID модального окна для генерации события
        const modalId = this.activeModal.id;
        
        // Специальная обработка для определенных модальных окон
        const specialModal = this.handleSpecialModalClose(modalId);
        if (specialModal) {
            // Особую обработку уже применили, выходим
            return true;
        }
        
        // Непосредственное закрытие модального окна
        this._executeModalClose(modalId, immediate);
        return true;
    }
    
    /**
     * Обработка особых случаев закрытия модальных окон
     */
    handleSpecialModalClose(modalId) {
        // QR-сканер требует особой обработки
        if (modalId === 'qrScannerModal') {
            // Остановка через контроллер
            if (window.qrScannerController) {
                try {
                    window.qrScannerController.stopScanner();
                } catch (e) {
                    console.error('Ошибка при остановке QR сканера', e);
                }
            }
        }
        
        // Возвращаем false, чтобы продолжить стандартное закрытие
        return false;
    }
    
    /**
     * Непосредственное выполнение закрытия модального окна
     */
    _executeModalClose(modalId, immediate = false) {
        // Принудительное немедленное закрытие для статических модальных окон
        if (!immediate && this.activeModal && this.activeModal.hasAttribute('data-static')) {
            immediate = true;
        }
        
        if (immediate) {
            // Немедленное закрытие без анимации
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
            
            this.activeModal.classList.remove('show', 'animate-in', 'animate-out');
            this.activeModal.style.display = 'none';
            
            // Разблокируем скролл
            this.unblockBodyScroll();
            
            // Генерируем события
            this.triggerModalEvent('modal.closed', { modalId });
            
            this.activeModal = null;
            
            // Сбрасываем флаг переходного состояния после небольшой задержки
            this.scheduleTask(() => {
                this.state.isTransitioning = false;
            }, 300);
        } else {
            // Закрытие с анимацией
            if (this.backdrop) {
                this.backdrop.classList.remove('show');
            }
            
            this.activeModal.classList.remove('animate-in');
            this.activeModal.classList.add('animate-out');
            
            // Ждем завершения анимации
            this.scheduleTask(() => {
                if (this.activeModal) {
                    this.activeModal.classList.remove('show', 'animate-out');
                    this.activeModal.style.display = 'none';
                    
                    // Разблокируем скролл
                    this.unblockBodyScroll();
                    
                    // Генерируем события
                    this.triggerModalEvent('modal.closed', { modalId });
                    
                    this.activeModal = null;
                }
                
                // Сбрасываем флаг переходного состояния с дополнительной задержкой
                this.scheduleTask(() => {
                    this.state.isTransitioning = false;
                }, 100);
            }, 300);
        }
    }
    
    /**
     * Генерирует событие для модального окна
     */
    triggerModalEvent(eventName, detail = {}) {
        const event = new CustomEvent(eventName, { detail });
        document.dispatchEvent(event);
    }
    
    /**
     * Планирует выполнение задачи с контролем времени жизни
     */
    scheduleTask(callback, delay) {
        const timeoutId = setTimeout(() => {
            callback();
            this.timeouts.delete(timeoutId);
        }, delay);
        
        this.timeouts.add(timeoutId);
        return timeoutId;
    }
    
    /**
     * Отменяет запланированную задачу
     */
    cancelTask(timeoutId) {
        if (timeoutId) {
            clearTimeout(timeoutId);
            this.timeouts.delete(timeoutId);
        }
    }
    
    /**
     * Очищает все запланированные задачи
     */
    clearAllTasks() {
        this.timeouts.forEach(id => clearTimeout(id));
        this.timeouts.clear();
    }
    
    /**
     * Блокирует скролл страницы - оптимизированный метод
     */
    blockBodyScroll() {
        if (this.state.scrollBlocked) return;
        
        // Сохраняем текущее положение скролла
        const scrollY = window.pageYOffset || document.documentElement.scrollTop;
        
        // Сохраняем оригинальные стили body
        this._originalBodyStyles = {
            overflow: document.body.style.overflow,
            position: document.body.style.position,
            top: document.body.style.top,
            width: document.body.style.width,
            paddingRight: document.body.style.paddingRight
        };
        
        // Вычисляем ширину скроллбара, чтобы предотвратить смещение контента
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        
        // Применяем стили для блокировки скролла
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = '100%';
        
        // Добавляем padding-right равный ширине скроллбара, чтобы избежать смещения контента
        if (scrollbarWidth > 0) {
            document.body.style.paddingRight = `${scrollbarWidth}px`;
        }
        
        // Добавляем класс для дополнительных стилей
        document.body.classList.add('modal-scroll-blocked');
        
        this.state.scrollBlocked = true;
        this._savedScrollY = scrollY;
    }
    
    /**
     * Разблокирует скролл страницы - оптимизированный метод
     */
    unblockBodyScroll() {
        if (!this.state.scrollBlocked) return;
        
        // Полностью очищаем inline стили тела документа для предотвращения закомментированных стилей
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.body.style.paddingRight = '';
        
        // Для гарантии полной очистки аттрибута style
        // в случае, если браузер сохраняет закомментированные стили
        if (document.body.getAttribute('style') === '' || 
            document.body.getAttribute('style')?.includes('/*')) {
            document.body.removeAttribute('style');
        }
        
        // Убираем класс
        document.body.classList.remove('modal-scroll-blocked');
        
        // Восстанавливаем позицию скролла
        if (this._savedScrollY !== undefined) {
            window.scrollTo(0, this._savedScrollY);
        }
        
        // Сбрасываем сохраненные значения
        this._savedScrollY = undefined;
        this._originalBodyStyles = undefined;
        
        this.state.scrollBlocked = false;
    }
}

// Создаем глобальный экземпляр системы модальных окон
window.modalPanel = new ModalPanelSystem();

// Удобные глобальные функции для работы с модальными окнами
window.openModalPanel = function(modalId) {
    if (window.modalPanel) {
        return window.modalPanel.openModal(modalId);
    }
    return false;
};

window.closeModalPanel = function() {
    if (window.modalPanel) {
        return window.modalPanel.closeModal();
    }
    return false;
};

// Оптимизированная функция для открытия QR-сканера
window.openQrScannerModal = function(iconElement) {
    // Предпочитаем использовать специализированный контроллер
    if (window.qrScannerController && typeof window.qrScannerController.open === 'function') {
        return window.qrScannerController.open(iconElement);
    }
    
    // Запасной вариант
    return openModalPanel('qrScannerModal');
};
</script>
