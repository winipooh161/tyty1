export class MobileNavEvents {
    constructor(core, scroll, popup) {
        this.core = core;
        this.scroll = scroll;
        this.popup = popup;
        
        // Состояние взаимодействия
        this.state = {
            touchStartX: 0,
            touchStartY: 0,
            isTouchMoved: false,
            isLongPress: false,
            activeIconId: null,
            lastInteractionTime: 0
        };
        
        // Таймеры
        this.timers = {
            longPress: null,
            debounce: null
        };
        
        // Константы
        this.constants = {
            longPressDelay: 500, // мс для срабатывания долгого нажатия
            debounceDelay: 300,  // мс для предотвращения дребезга событий
            minSwipeDistance: 30 // минимальное расстояние для свайпа
        };
        
        // Кэш для обработчиков событий
        this._eventHandlers = new Map();
        
        // Инициализация
        this.init();
    }
    
    init() {
        // Безопасная инициализация с проверкой готовности DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupEventListeners();
            });
        } else {
            // DOM уже загружен
            setTimeout(() => this.setupEventListeners(), 100);
        }
    }
    
    setupEventListeners() {
        // Проверяем инициализацию ядра навигации
        if (!this.core || !this.core.container) {
            console.warn('MobileNavEvents: Необходимо инициализировать ядро навигации');
            // Пробуем повторно через 500мс
            setTimeout(() => this.setupEventListeners(), 500);
            return;
        }
        
        // Настройка обработчиков модальных окон
        this.setupModalListeners();
        
        // События касания на навигации
        this.setupTouchEvents();
        
        // События клика на иконках с делегированием событий
        this.setupClickEvents();
        
        console.log('MobileNavEvents: События успешно инициализированы');
    }
    
    // Оптимизированная настройка обработчиков модальных окон
    setupModalListeners() {
        // Используем один обработчик для всех модальных событий
        const handleModalEvent = (event) => {
            const eventType = event.type;
            const detail = event.detail || {};
            const modalId = detail.modalId;
            const sourceIconId = detail.sourceIconId;
            
            if (eventType === 'modal.opened') {
                console.log('⚡️ Модальное окно открыто:', { modalId, sourceIconId });
                
                // Обработка открытия модального окна
                if (modalId && sourceIconId) {
                    // Обновление активной иконки с проверкой предыдущего состояния
                    if (this.state.activeIconId === sourceIconId) {
                        this.core.restoreIcon(sourceIconId);
                    }
                    
                    // Устанавливаем новую активную иконку
                    this.state.activeIconId = sourceIconId;
                    
                    // Конвертируем иконку в кнопку "назад"
                    this.core.convertIconToBackButton(sourceIconId);
                }
            } else if (eventType === 'modal.closed') {
                console.log('⚡️ Модальное окно закрыто:', { modalId, activeIconId: this.state.activeIconId });
                
                // Обработка закрытия модального окна
                if (modalId && this.state.activeIconId) {
                    // Восстанавливаем иконку и сбрасываем состояние
                    this.core.restoreIcon(this.state.activeIconId);
                    this.state.activeIconId = null;
                }
            }
        };
        
        // Регистрируем единый обработчик для событий открытия и закрытия
        document.addEventListener('modal.opened', handleModalEvent);
        document.addEventListener('modal.closed', handleModalEvent);
        
        // Интеграция с существующей модальной системой
        this.injectModalSystemHandlers();
    }
    
    // Внедрение оптимизированных обработчиков в модальную систему
    injectModalSystemHandlers() {
        if (!window.modalPanel || window.modalPanel._methodsModified) return;
        
        try {
            // Сохраняем оригинальные методы
            const originalOpenModal = window.modalPanel.openModal;
            const originalCloseModal = window.modalPanel.closeModal;
            
            // Переопределяем метод открытия с дополнительной логикой
            window.modalPanel.openModal = (modalId) => {
                // Проверяем дебаунсинг
                const now = Date.now();
                if (now - this.state.lastInteractionTime < this.constants.debounceDelay) return false;
                this.state.lastInteractionTime = now;
                
                // Вызываем оригинальный метод
                const result = originalOpenModal.call(window.modalPanel, modalId);
                
                // Если успешно открыто, генерируем событие
                if (result) {
                    let sourceInfo = this.getModalSourceInfo(modalId);
                    
                    if (sourceInfo) {
                        // Создаем событие открытия модального окна
                        this.triggerModalEvent('modal.opened', {
                            modalId,
                            sourceIconId: sourceInfo.iconId
                        });
                    }
                }
                
                return result;
            };
            
            // Переопределяем метод закрытия
            window.modalPanel.closeModal = (immediate = false) => {
                // Получаем ID активного модального окна
                const modalId = window.modalPanel.activeModal?.id;
                
                // Вызываем оригинальный метод
                const result = originalCloseModal.call(window.modalPanel, immediate);
                
                // Если было активное модальное окно, генерируем событие
                if (modalId) {
                    this.triggerModalEvent('modal.closed', { modalId });
                }
                
                return result;
            };
            
            // Отмечаем, что методы были модифицированы
            window.modalPanel._methodsModified = true;
            console.log('MobileNavEvents: Методы модальной системы успешно модифицированы');
        } catch (error) {
            console.error('MobileNavEvents: Ошибка при модификации методов модальной системы:', error);
        }
    }
    
    // Получение информации об источнике модального окна
    getModalSourceInfo(modalId) {
        // Проверяем в modalSources модальной системы
        if (window.modalPanel?.modalSources?.has(modalId)) {
            return window.modalPanel.modalSources.get(modalId);
        }
        
        // Проверяем в нашей системе popup
        if (this.popup?.modalTriggers?.has(modalId)) {
            return this.popup.modalTriggers.get(modalId);
        }
        
        return null;
    }
    
    // Генерация события модального окна
    triggerModalEvent(eventName, detail = {}) {
        const event = new CustomEvent(eventName, { detail });
        document.dispatchEvent(event);
    }
    
    // Настройка обработчиков сенсорных событий
    setupTouchEvents() {
        // Используем делегирование событий для повышения производительности
        this._addEventHandler(this.core.container, 'touchstart', (e) => {
            // Определяем начальные координаты
            const touch = e.touches[0];
            this.state.touchStartX = touch.clientX;
            this.state.touchStartY = touch.clientY;
            this.state.isTouchMoved = false;
            this.state.isLongPress = false;
            
            // Находим иконку под касанием для более точного определения
            const touchedElement = document.elementFromPoint(this.state.touchStartX, this.state.touchStartY);
            const iconWrapper = touchedElement ? touchedElement.closest('.mb-icon-wrapper') : null;
            
            if (iconWrapper) {
                // Добавляем визуальный эффект при касании
                iconWrapper.classList.add('mb-touch-active');
                
                // Запускаем таймер для долгого нажатия с очисткой предыдущего
                clearTimeout(this.timers.longPress);
                
                this.timers.longPress = setTimeout(() => {
                    // Срабатывает только если палец не двигался
                    if (!this.state.isTouchMoved) {
                        this.state.isLongPress = true;
                        this.handleLongPress(iconWrapper);
                    }
                }, this.constants.longPressDelay);
            }
        }, { passive: true });
        
        // Отслеживание движения пальца
        this._addEventHandler(this.core.container, 'touchmove', (e) => {
            // Отменяем долгое нажатие при движении пальца
            if (this.state.touchStartX && this.state.touchStartY) {
                const touch = e.touches[0];
                const deltaX = Math.abs(touch.clientX - this.state.touchStartX);
                const deltaY = Math.abs(touch.clientY - this.state.touchStartY);
                
                // Если движение превысило порог, отменяем долгое нажатие
                if (deltaX > 10 || deltaY > 10) {
                    this.state.isTouchMoved = true;
                    clearTimeout(this.timers.longPress);
                    
                    // Снимаем эффект активного нажатия
                    document.querySelectorAll('.mb-touch-active').forEach(el => {
                        el.classList.remove('mb-touch-active');
                    });
                }
            }
        }, { passive: true });
        
        // Обработка завершения касания
        this._addEventHandler(this.core.container, 'touchend', () => {
            // Очищаем таймер долгого нажатия
            clearTimeout(this.timers.longPress);
            
            // Удаляем визуальный эффект активного нажатия
            document.querySelectorAll('.mb-touch-active').forEach(el => {
                el.classList.remove('mb-touch-active');
            });
        }, { passive: true });
    }
    
    // Настройка обработчиков кликов с делегированием
    setupClickEvents() {
        // Используем делегирование событий для экономии ресурсов
        document.addEventListener('click', (e) => {
            // Проверяем дебаунсинг
            const now = Date.now();
            if (now - this.state.lastInteractionTime < this.constants.debounceDelay) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            this.state.lastInteractionTime = now;
            
            // Обработка клика на элементы модальных окон
            const modalTrigger = e.target.closest('[data-icon-id][data-modal="true"]');
            if (modalTrigger) {
                // Предотвращаем стандартное поведение
                e.preventDefault();
                e.stopPropagation();
                
                const modalId = modalTrigger.getAttribute('data-modal-target');
                const iconId = modalTrigger.getAttribute('data-icon-id');
                
                if (modalId && iconId) {
                    // Сохраняем связь модального окна с иконкой для будущих использований
                    this.popup.modalTriggers.set(modalId, {
                        element: modalTrigger,
                        iconId: iconId
                    });
                    
                    // Открываем модальное окно через соответствующий контроллер
                    if (iconId === 'qr-scanner' && window.qrScannerController) {
                        window.qrScannerController.open(e);
                    } else if (window.modalPanel) {
                        window.modalPanel.openModal(modalId);
                    }
                }
            }
        });
    }
    
    // Обработка долгого нажатия на иконку
    handleLongPress(iconWrapper) {
        // Получаем ID иконки
        const iconId = iconWrapper.getAttribute('data-icon-id');
        if (!iconId) return;
        
        // Добавляем класс для визуального эффекта
        iconWrapper.classList.add('mb-long-press');
        
        // Тактильная обратная связь
        this.provideTactileFeedback(50);
        
        // Показываем всплывающее меню с небольшой задержкой для лучшего UX
        setTimeout(() => {
            if (this.popup && typeof this.popup.showPopup === 'function') {
                this.popup.showPopup(iconId);
            }
            
            // Удаляем эффект долгого нажатия
            iconWrapper.classList.remove('mb-long-press', 'mb-touch-active');
        }, 150);
    }
    
    // Предоставляет тактильную обратную связь
    provideTactileFeedback(duration = 30) {
        // Проверка наличия явного взаимодействия пользователя
        const hasInteracted = 
            this.popup?.userHasInteracted === true || 
            window.userHasInteractedWithPage === true;
        
        if (hasInteracted && navigator.vibrate && 
            !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            try {
                // Используем более короткую вибрацию для снижения раздражения
                navigator.vibrate(Math.min(duration, 20));
            } catch (e) {
                // Игнорируем ошибки vibrate API
            }
        }
    }
    
    // Безопасное добавление обработчика событий с сохранением ссылки
    _addEventHandler(element, eventType, handler, options = {}) {
        if (!element) return;
        
        // Сохраняем обработчик для возможности удаления
        if (!this._eventHandlers.has(element)) {
            this._eventHandlers.set(element, new Map());
        }
        
        const elementHandlers = this._eventHandlers.get(element);
        if (!elementHandlers.has(eventType)) {
            elementHandlers.set(eventType, []);
        }
        
        elementHandlers.get(eventType).push(handler);
        
        // Добавляем событие
        element.addEventListener(eventType, handler, options);
    }
    
    // Удаление всех обработчиков для очистки ресурсов
    destroy() {
        // Очищаем все таймеры
        Object.values(this.timers).forEach(timer => {
            if (timer) clearTimeout(timer);
        });
        
        // Удаляем все обработчики событий
        this._eventHandlers.forEach((typeHandlers, element) => {
            typeHandlers.forEach((handlers, eventType) => {
                handlers.forEach(handler => {
                    element.removeEventListener(eventType, handler);
                });
            });
        });
        
        this._eventHandlers.clear();
    }
}
