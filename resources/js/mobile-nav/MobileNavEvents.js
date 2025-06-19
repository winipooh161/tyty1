export class MobileNavEvents {
    constructor(core, scroll, popup) {
        this.core = core;
        this.scroll = scroll;
        this.popup = popup;
        
        // Состояние
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.isTouchMoved = false;
        this.isLongPress = false;
        this.longPressTimer = null;
        this.longPressDelay = 500; // ms для срабатывания долгого нажатия
        this.activeIconId = null; // Текущая активная иконка для модального окна
        
        // Инициализация после создания объекта
        this.init();
    }
    
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupEventListeners();
            });
        } else {
            // DOM уже загружен
            setTimeout(() => this.setupEventListeners(), 500);
        }
    }
    
    setupEventListeners() {
        if (!this.core.isInitialized || !this.core.container) {
            console.warn('MobileNavEvents: Ядро навигации не инициализировано');
            return;
        }

        // Слушаем события открытия/закрытия модальных окон
        this.setupModalListeners();
        
        // События касания на навигации
        this.setupTouchEvents();
        
        // События клика на иконках
        this.setupClickEvents();
        
        console.log('MobileNavEvents: События инициализированы');
    }
    
    setupModalListeners() {
        // Прослушиваем события открытия модальных окон
        document.addEventListener('modal.opened', (event) => {
            const modalId = event.detail?.modalId;
            
            // Получаем sourceIconId из модального события или из modalTriggers
            let sourceIconId = event.detail?.sourceIconId;
            
            // Если sourceIconId не определен, пробуем получить его из modalTriggers
            if (!sourceIconId && modalId && this.popup.modalTriggers.has(modalId)) {
                sourceIconId = this.popup.modalTriggers.get(modalId).iconId;
            }
            
            console.log('⚡️ Событие modal.opened получено:', { modalId, sourceIconId });
            
            // Проверяем, есть ли ID иконки и модального окна
            if (modalId && sourceIconId) {
                // Если иконка уже активна, сначала восстанавливаем её,
                // чтобы обеспечить корректное обновление обработчиков
                if (this.activeIconId === sourceIconId) {
                    console.log(`🔄 Обновляем обработчики для иконки ${sourceIconId}`);
                    // Восстанавливаем иконку перед повторным преобразованием
                    this.core.restoreIcon(sourceIconId);
                } else {
                    console.log(`🔄 Преобразуем иконку ${sourceIconId} в кнопку "назад" для модалки ${modalId}`);
                }
                
                this.activeIconId = sourceIconId;
                
                // Всегда преобразуем иконку в кнопку "назад" для обновления обработчиков
                const success = this.core.convertIconToBackButton(sourceIconId);
                console.log(`Результат преобразования: ${success ? 'успешно' : 'ошибка'}`);
            }
        });
        
        // Прослушиваем события закрытия модальных окон
        document.addEventListener('modal.closed', (event) => {
            const modalId = event.detail?.modalId;
            
            console.log('⚡️ Событие modal.closed получено:', { modalId, activeIconId: this.activeIconId });
            
            // Если закрыто модальное окно и у нас есть активная иконка
            if (modalId && this.activeIconId) {
                console.log(`🔄 Восстанавливаем оригинальную иконку ${this.activeIconId}`);
                
                // Восстанавливаем исходную иконку
                const success = this.core.restoreIcon(this.activeIconId);
                console.log(`Результат восстановления: ${success ? 'успешно' : 'ошибка'}`);
                this.activeIconId = null;
            }
        });
        
        // Связываем с модальной системой, если она существует
        if (window.modalPanel) {
            // Проверяем, не модифицированы ли методы уже
            if (!window.modalPanel._methodsModified) {
                const originalOpenModal = window.modalPanel.openModal;
                const originalCloseModal = window.modalPanel.closeModal;
                
                // Модифицируем метод открытия модального окна
                window.modalPanel.openModal = (modalId) => {
                    const result = originalOpenModal.call(window.modalPanel, modalId);
                    
                    if (result) {
                        // Если есть информация о триггере модального окна
                        let triggerInfo = null;
                        
                        // Проверяем наличие информации в modalSources модальной системы
                        if (window.modalPanel.modalSources && window.modalPanel.modalSources.has(modalId)) {
                            triggerInfo = window.modalPanel.modalSources.get(modalId);
                        } 
                        // Если нет, проверяем в popup.modalTriggers
                        else if (this.popup && this.popup.modalTriggers.has(modalId)) {
                            triggerInfo = this.popup.modalTriggers.get(modalId);
                        }
                        
                        if (triggerInfo && triggerInfo.iconId) {
                            // Создаем и отправляем событие открытия модального окна
                            const event = new CustomEvent('modal.opened', {
                                detail: {
                                    modalId: modalId,
                                    sourceIconId: triggerInfo.iconId
                                }
                            });
                            document.dispatchEvent(event);
                        }
                    }
                    
                    return result;
                };
                
                // Модифицируем метод закрытия модального окна
                window.modalPanel.closeModal = (immediate = false) => {
                    // Получаем ID активного модального окна перед закрытием
                    const modalId = window.modalPanel.activeModal?.id;
                    
                    // Вызываем оригинальный метод
                    originalCloseModal.call(window.modalPanel, immediate);
                    
                    if (modalId) {
                        // Создаем и отправляем событие закрытия модального окна
                        const event = new CustomEvent('modal.closed', {
                            detail: {
                                modalId: modalId
                            }
                        });
                        document.dispatchEvent(event);
                    }
                };
                
                // Отмечаем, что методы уже модифицированы
                window.modalPanel._methodsModified = true;
            }
        }
    }
    
    setupTouchEvents() {
        // Обработка начала касания
        this.core.container.addEventListener('touchstart', (e) => {
            // Сохраняем начальные координаты касания
            this.touchStartX = e.touches[0].clientX;
            this.touchStartY = e.touches[0].clientY;
            this.isTouchMoved = false;
            
            // Определяем элемент под пальцем
            const touchedElement = document.elementFromPoint(this.touchStartX, this.touchStartY);
            const iconWrapper = touchedElement ? touchedElement.closest('.mb-icon-wrapper') : null;
            
            if (iconWrapper) {
                // Добавляем визуальный эффект при касании
                iconWrapper.classList.add('mb-touch-active');
                
                // Очищаем существующий таймер долгого нажатия, если есть
                if (this.longPressTimer) {
                    clearTimeout(this.longPressTimer);
                }
                
                // Устанавливаем таймер для долгого нажатия
                this.longPressTimer = setTimeout(() => {
                    if (!this.isTouchMoved) {
                        this.isLongPress = true;
                        this.handleLongPress(iconWrapper);
                    }
                }, this.longPressDelay);
            }
        }, { passive: true });
        
        // Обработка перемещения пальца
        this.core.container.addEventListener('touchmove', (e) => {
            if (this.longPressTimer) {
                // Определяем, было ли значимое движение пальца
                const touchX = e.touches[0].clientX;
                const touchY = e.touches[0].clientY;
                const deltaX = Math.abs(touchX - this.touchStartX);
                const deltaY = Math.abs(touchY - this.touchStartY);
                
                // Если палец переместился на значимое расстояние, отменяем долгое нажатие
                if (deltaX > 10 || deltaY > 10) {
                    this.isTouchMoved = true;
                    clearTimeout(this.longPressTimer);
                    this.longPressTimer = null;
                    
                    // Удаляем эффект активного нажатия
                    document.querySelectorAll('.mb-touch-active').forEach(el => {
                        el.classList.remove('mb-touch-active');
                    });
                }
            }
        }, { passive: true });
        
        // Обработка завершения касания
        this.core.container.addEventListener('touchend', (e) => {
            // Удаляем эффект активного нажатия
            document.querySelectorAll('.mb-touch-active').forEach(el => {
                el.classList.remove('mb-touch-active');
            });
            
            // Очищаем таймер долгого нажатия
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            
            // Сбрасываем состояние долгого нажатия
            this.isLongPress = false;
        }, { passive: true });
    }
    
    setupClickEvents() {
        // Находим все иконки с атрибутом data-modal
        const modalTriggers = document.querySelectorAll('.mb-icon-wrapper[data-modal="true"]');
        
        modalTriggers.forEach(trigger => {
            const modalId = trigger.getAttribute('data-modal-target');
            const iconId = trigger.getAttribute('data-icon-id');
            
            if (modalId && iconId) {
                // Добавляем информацию о триггере модального окна в popup
                this.popup.modalTriggers.set(modalId, {
                    element: trigger,
                    iconId: iconId
                });
                
                // Специальная обработка для QR-сканера
                if (iconId === 'qr-scanner') {
                    trigger.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        console.log('QR Scanner клик обработан в MobileNavEvents');
                        
                        // Открываем модальное окно через глобальную функцию
                        if (window.openQrScannerModal) {
                            window.openQrScannerModal(trigger);
                        } else if (window.modalPanel) {
                            window.modalPanel.openModal(modalId);
                        }
                    });
                }
            }
        });
    }
    
    handleLongPress(iconWrapper) {
        // Получаем ID иконки
        const iconId = iconWrapper.getAttribute('data-icon-id');
        if (!iconId) return;
        
        // Добавляем класс для эффекта долгого нажатия
        iconWrapper.classList.add('mb-long-press');
        
        // Вибрация для тактильной обратной связи
        if (navigator.vibrate) {
            try {
                navigator.vibrate(50);
            } catch (e) {
                // Игнорируем ошибки vibrate API
            }
        }
        
        // Показываем всплывающее меню
        setTimeout(() => {
            this.popup.showPopup(iconId);
            
            // Удаляем эффект долгого нажатия
            iconWrapper.classList.remove('mb-long-press');
        }, 300);
    }
}
