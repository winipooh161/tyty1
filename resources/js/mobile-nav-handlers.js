/**
 * Глобальные обработчики для мобильной навигации
 * Обеспечивает интеграцию с различными компонентами приложения
 */
(function() {
    'use strict';
    
    // Глобальные объекты для взаимодействия с другими компонентами
    window.mobileNavUtils = window.mobileNavUtils || {};
    
    // Система хранения состояний
    const navState = {
        isScrollBlocked: false,
        originalBodyStyles: null,
        savedScrollY: 0,
        modalTimers: new Set(),
        activeModals: new Set(),
        transitionInProgress: false,
        lastActionTime: 0
    };
    
    // Константы
    const constants = {
        transitionDuration: 300, // мс для анимации переходов
        debounceThreshold: 500,  // мс для предотвращения дребезга
        vibrationDuration: 20,   // мс для тактильной обратной связи
    };
    
    // Инициализация после загрузки DOM
    document.addEventListener('DOMContentLoaded', init);
    
    /**
     * Основная инициализация модуля
     */
    function init() {
        // Добавляем общие утилиты
        setupGlobalUtils();
        
        // Настраиваем перехват модальной системы
        setupModalSystemIntegration();
        
        // Отслеживаем взаимодействие пользователя
        setupUserInteractionTracking();
        
        // Интеграция с QR-сканером
        setupQrScannerIntegration();
        
        // Настройка сохранения формы в навигации
        setupFormSaveHandlers();
        
        // Интеграция с сервисными рабочими (PWA)
        setupServiceWorkerIntegration();
        
        console.log('MobileNavHandlers: Инициализация успешно завершена');
    }
    
    /**
     * Настройка глобальных утилит для мобильной навигации
     */
    function setupGlobalUtils() {
        // Функции для блокировки/разблокировки скролла
        window.mobileNavUtils.blockBodyScroll = blockBodyScroll;
        window.mobileNavUtils.unblockBodyScroll = unblockBodyScroll;
        
        // Функция для отображения всплывающих уведомлений
        window.mobileNavUtils.showToast = showToast;
        
        // Функция для тактильной обратной связи с проверкой взаимодействия
        window.mobileNavUtils.vibrate = provideTactileFeedback;
        
        // Оптимизированная проверка доступности вибрации
        window.mobileNavUtils.canUseVibration = function() {
            return navigator.vibrate && 
                   typeof window.userHasInteractedWithPage !== 'undefined' &&
                   window.userHasInteractedWithPage === true &&
                   !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        };
    }
    
    /**
     * Блокирует скролл страницы
     */
    function blockBodyScroll() {
        if (navState.isScrollBlocked) return;
        
        // Сохраняем текущее положение скролла
        navState.savedScrollY = window.pageYOffset || document.documentElement.scrollTop;
        
        // Сохраняем оригинальные стили body
        navState.originalBodyStyles = {
            overflow: document.body.style.overflow,
            position: document.body.style.position,
            top: document.body.style.top,
            width: document.body.style.width,
            paddingRight: document.body.style.paddingRight
        };
        
        // Вычисляем ширину скроллбара
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        
        // Применяем стили для блокировки скролла
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${navState.savedScrollY}px`;
        document.body.style.width = '100%';
        
        // Компенсируем исчезновение скроллбара
        if (scrollbarWidth > 0) {
            document.body.style.paddingRight = `${scrollbarWidth}px`;
        }
        
        // Добавляем класс для дополнительных стилей
        document.body.classList.add('modal-scroll-blocked');
        
        navState.isScrollBlocked = true;
    }
    
    /**
     * Разблокирует скролл страницы
     */
    function unblockBodyScroll() {
        if (!navState.isScrollBlocked) return;
        
        // Восстанавливаем стили
        if (navState.originalBodyStyles) {
            Object.entries(navState.originalBodyStyles).forEach(([prop, value]) => {
                document.body.style[prop] = value;
            });
        } else {
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.paddingRight = '';
        }
        
        // Полная очистка атрибута style при необходимости
        const currentStyle = document.body.getAttribute('style');
        if (currentStyle === '' || (currentStyle && currentStyle.includes('/*'))) {
            document.body.removeAttribute('style');
        }
        
        // Удаляем маркер
        document.body.classList.remove('modal-scroll-blocked');
        
        // Восстанавливаем позицию скролла
        if (navState.savedScrollY !== undefined) {
            window.scrollTo(0, navState.savedScrollY);
        }
        
        // Сбрасываем сохраненные значения
        navState.savedScrollY = undefined;
        navState.originalBodyStyles = undefined;
        
        navState.isScrollBlocked = false;
    }
    
    /**
     * Показывает всплывающее уведомление
     */
    function showToast(message, type = 'info', duration = 3000) {
        // Создаем элемент уведомления
        const toast = document.createElement('div');
        toast.className = `mobile-nav-toast ${type}`;
        toast.textContent = message;
        
        // Добавляем на страницу
        document.body.appendChild(toast);
        
        // Показываем через RAF для плавной анимации
        requestAnimationFrame(() => {
            // Небольшая задержка для включения анимации
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Скрываем через заданное время
            setTimeout(() => {
                toast.classList.remove('show');
                
                // Удаляем после завершения анимации
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, constants.transitionDuration);
            }, duration);
        });
    }
    
    /**
     * Настройка интеграции с модальной системой
     */
    function setupModalSystemIntegration() {
        // Проверяем, существует ли модальная система
        if (typeof ModalPanelSystem === 'function' && !window.modalPanel) {
            // Создаем экземпляр модальной системы
            window.modalPanel = new ModalPanelSystem();
            
            // Устанавливаем глобальные функции
            window.openModalPanel = function(modalId) {
                return window.modalPanel?.openModal(modalId) || false;
            };
            
            window.closeModalPanel = function() {
                return window.modalPanel?.closeModal() || false;
            };
        }
        
        // Устанавливаем обработчики для модальных окон
        setupModalEventListeners();
    }
    
    /**
     * Настройка обработчиков событий модальных окон
     */
    function setupModalEventListeners() {
        // Следим за открытием модальных окон
        document.addEventListener('modal.opened', function(event) {
            // Активируем тактильную обратную связь
            window.mobileNavUtils.vibrate();
            
            // Добавляем в список активных модальных окон
            if (event.detail?.modalId) {
                navState.activeModals.add(event.detail.modalId);
            }
        });
        
        // Следим за закрытием модальных окон
        document.addEventListener('modal.closed', function(event) {
            // Удаляем из списка активных модальных окон
            if (event.detail?.modalId) {
                navState.activeModals.delete(event.detail.modalId);
            }
        });
    }
    
    /**
     * Отслеживание взаимодействия пользователя
     */
    function setupUserInteractionTracking() {
        // Отслеживаем первое взаимодействие для активации вибрации
        const interactionEvents = ['touchstart', 'mousedown', 'keydown', 'scroll'];
        
        const registerUserInteraction = function() {
            window.userHasInteractedWithPage = true;
            
            // Удаляем обработчики после первого взаимодействия
            interactionEvents.forEach(event => {
                document.removeEventListener(event, registerUserInteraction, { passive: true });
            });
        };
        
        // Устанавливаем обработчики
        interactionEvents.forEach(event => {
            document.addEventListener(event, registerUserInteraction, { passive: true });
        });
    }
    
    /**
     * Настройка интеграции с QR-сканером
     */
    function setupQrScannerIntegration() {
        // Глобальная функция для открытия QR-сканера
        window.openQrScannerModal = function(iconElement) {
            // Используем специализированный контроллер, если доступен
            if (window.qrScannerController && typeof window.qrScannerController.open === 'function') {
                return window.qrScannerController.open(iconElement);
            }
            
            // Запасной вариант - через обычную модальную систему
            return openModalPanel('qrScannerModal');
        };
    }
    
    /**
     * Настройка обработчиков сохранения формы в навигации
     */
    function setupFormSaveHandlers() {
        // Находим кнопку сохранения в навигации
        const saveTemplateBtn = document.getElementById('save-template-btn');
        
        if (saveTemplateBtn) {
            saveTemplateBtn.addEventListener('click', function() {
                // Проверка таймера для предотвращения двойных кликов
                const now = Date.now();
                if (now - navState.lastActionTime < constants.debounceThreshold) {
                    return false;
                }
                navState.lastActionTime = now;
                
                // Тактильная обратная связь
                window.mobileNavUtils.vibrate(30);
                
                // Вызываем функцию сохранения формы, если существует
                if (typeof window.saveTemplateForm === 'function') {
                    try {
                        window.saveTemplateForm();
                    } catch (error) {
                        console.error('Ошибка при сохранении формы:', error);
                        window.mobileNavUtils.showToast('Ошибка при сохранении: ' + error.message, 'error');
                    }
                } else {
                    // Альтернативное сохранение - находим стандартную форму
                    const saveForm = document.getElementById('template-save-form') || 
                                    document.querySelector('form[data-save-form]');
                                   
                    if (saveForm) {
                        try {
                            saveForm.submit();
                        } catch (error) {
                            console.error('Ошибка при отправке формы:', error);
                            window.mobileNavUtils.showToast('Не удалось отправить форму', 'error');
                        }
                    } else {
                        window.mobileNavUtils.showToast('Форма для сохранения не найдена', 'error');
                    }
                }
            });
        }
    }
    
    /**
     * Интеграция с сервисными рабочими для PWA
     */
    function setupServiceWorkerIntegration() {
        // Регистрация сервис-воркера
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js').then(registration => {
                    console.log('ServiceWorker зарегистрирован успешно:', registration.scope);
                }).catch(error => {
                    console.log('Ошибка регистрации ServiceWorker:', error);
                });
            });
        }
    }
    
    // Инициализируем стили для уведомлений
    function initToastStyles() {
        // Проверяем наличие стилей
        if (!document.getElementById('mobile-nav-toast-styles')) {
            const style = document.createElement('style');
            style.id = 'mobile-nav-toast-styles';
            style.textContent = `
                .mobile-nav-toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    background-color: #343a40;
                    color: white;
                    border-radius: 8px;
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
                    z-index: 2000;
                    opacity: 0;
                    transform: translateY(-20px);
                    transition: opacity 0.3s ease, transform 0.3s ease;
                    max-width: 80%;
                    font-size: 14px;
                    font-weight: 500;
                }
                
                .mobile-nav-toast.show {
                    opacity: 1;
                    transform: translateY(0);
                }
                
                .mobile-nav-toast.success {
                    background-color: #28a745;
                }
                
                .mobile-nav-toast.error {
                    background-color: #dc3545;
                }
                
                .mobile-nav-toast.warning {
                    background-color: #ffc107;
                    color: #333;
                }
                
                .mobile-nav-toast.info {
                    background-color: #17a2b8;
                }
                
                @media (max-width: 576px) {
                    .mobile-nav-toast {
                        left: 20px;
                        right: 20px;
                        text-align: center;
                    }
                }
            `;
            
            document.head.appendChild(style);
        }
    }
    
    // Инициализация стилей после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToastStyles);
    } else {
        initToastStyles();
    }
})();
