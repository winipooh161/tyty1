export class MobileNavScroll {
    constructor(core) {
        this.core = core;
        this.isScrolling = false;
        this.scrollTimeout = null;
        this.animationFrame = null;
        this.isCentering = false; // Оставляем переменную для совместимости
        this.centeringQueue = []; // Оставляем для совместимости
        this.lastDebounceTime = 0;
        this.debounceThreshold = 150;
        this.lastScrollLeft = 0;
        this.scrollDirection = 0;
        this.debounceTimeout = null;
        
        // Улучшенные переменные для управления скроллом страницы
        this.lastPageScroll = 0;
        this.pageScrollTimeout = null;
        this.pageScrollThreshold = 10; // минимальное расстояние прокрутки для реакции
        this.hideNavigationTimeout = null;
        this.isNavigationHidden = false;
        this.lastUserActionTime = Date.now(); // Время последнего действия пользователя
        this.inactivityTimeout = null; // Таймер бездействия
        this.inactivityThreshold = 1500; // Порог бездействия в миллисекундах (1.5 секунды)

        this.inertiaEnabled = true; // Включение инерции для плавного скролла
        this.inertiaFactor = 0.92; // Фактор инерции (1 - без затухания, 0 - мгновенная остановка)
        this.inertiaThreshold = 0.5; // Порог остановки инерции
        this.momentumValue = 0; // Текущее значение импульса
        this.rafId = null; // ID для requestAnimationFrame

        // Добавляем флаг для отслеживания взаимодействия пользователя
        this.userHasInteracted = false;
        
        // Запускаем систему отслеживания взаимодействия
        this.initUserInteractionTracking();

        // В конструкторе добавим свойство для отслеживания времени последнего скролла
        this.lastScrollTime = 0;
        
        // Переносим инициализацию слушателя скролла в отдельный метод, который будет вызван позже
        this.setupScrollTimeTracking();
        
        // Инициализируем кэш для обработчиков событий
        this._eventHandlers = new Map();
        
        // Флаги для управления скроллом и состоянием навигации
        this._scrollBlocked = false;
        this._originalBodyStyles = null;
        this._savedScrollY = 0;
        
        // Инициализация системы слежения за страницей
        this.setupPageScrollListener();
    }

    // Новый метод для безопасной настройки отслеживания времени скролла
    setupScrollTimeTracking() {
        // Используем DOMContentLoaded для безопасной инициализации
        if (document.readyState === 'loading') {
            this._addSafeEventListener(document, 'DOMContentLoaded', () => {
                this.setupScrollListener();
            });
        } else {
            // DOM уже загружен, пробуем настроить сейчас или через таймаут
            setTimeout(() => this.setupScrollListener(), 100);
        }
    }

    // Метод для фактического добавления слушателя скролла
    setupScrollListener() {
        // Проверяем, инициализирован ли контейнер
        if (this.core && this.core.container) {
            this._addSafeEventListener(this.core.container, 'scroll', () => {
                this.lastScrollTime = Date.now();
            }, { passive: true });
        } else {
            // Если контейнер всё еще не доступен, попробуем позже
            setTimeout(() => this.setupScrollListener(), 500);
        }
    }
    
    // Оптимизированный метод для безопасного добавления обработчиков событий
    _addSafeEventListener(element, eventType, handler, options = {}) {
        if (!element) return;
        
        // Сохраняем обработчик для возможности последующего удаления
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
    
    // Оптимизированный метод для удаления обработчиков событий
    _removeSafeEventListener(element, eventType, handlerToRemove = null) {
        if (!element || !this._eventHandlers.has(element)) return;
        
        const elementHandlers = this._eventHandlers.get(element);
        if (!elementHandlers.has(eventType)) return;
        
        if (handlerToRemove) {
            // Удаляем конкретный обработчик
            const handlers = elementHandlers.get(eventType);
            const index = handlers.indexOf(handlerToRemove);
            
            if (index !== -1) {
                element.removeEventListener(eventType, handlerToRemove);
                handlers.splice(index, 1);
            }
        } else {
            // Удаляем все обработчики для события
            elementHandlers.get(eventType).forEach(handler => {
                element.removeEventListener(eventType, handler);
            });
            elementHandlers.delete(eventType);
        }
    }

    applyInertia(velocity) {
        if (!this.inertiaEnabled || Math.abs(velocity) < this.inertiaThreshold) return;
        
        this.momentumValue = velocity * 15; // Коэффициент для расчета инерции
        
        // Останавливаем предыдущую анимацию инерции, если она запущена
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
        
        // Функция для анимации инерции с оптимизацией для производительности
        const animateInertia = () => {
            if (!this.core || !this.core.container) {
                this.rafId = null;
                return;
            }
            
            // Применяем инерцию только если значение достаточно высокое
            if (Math.abs(this.momentumValue) > this.inertiaThreshold) {
                this.core.container.scrollLeft += this.momentumValue;
                
                // Уменьшаем момент с учетом фактора инерции
                this.momentumValue *= this.inertiaFactor;
                
                this.rafId = requestAnimationFrame(animateInertia);
            } else {
                // Завершаем инерцию
                this.rafId = null;
            }
        };
        
        // Запускаем анимацию инерции
        this.rafId = requestAnimationFrame(animateInertia);
    }
    
    // Новый метод для обнаружения скроллера
    detectScrollContainer() {
        if (!this.core.container) {
            const container = document.getElementById('nav-scroll-container');
            if (container) {
                this.core.container = container;
                return true;
            }
            return false;
        }
        return true;
    }

    // Оптимизированные методы для работы с модальными окнами
    /**
     * Отображение модального окна с улучшенной производительностью
     */
    showModalPanel(modalId, options = {}) {
        if (window.modalPanel) {
            return window.modalPanel.openModal(modalId);
        }
        
        // Запасной вариант, если нет глобальной модальной системы
        const modal = document.getElementById(modalId);
        if (!modal) return false;
        
        // Блокировка скролла
        this.blockBodyScroll();
        
        // Показываем модальное окно
        modal.classList.add('show', 'animate-in');
        modal.style.display = 'flex';
        
        // Показываем фон
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.classList.add('show');
        }
        
        return true;
    }
    
    /**
     * Скрытие модального окна с оптимизацией
     */
    hideModalPanel(modalId) {
        if (window.modalPanel) {
            return window.modalPanel.closeModal();
        }
        
        // Запасной вариант
        const modal = document.getElementById(modalId);
        if (!modal) return false;
        
        // Плавно скрываем модальное окно
        modal.classList.remove('animate-in');
        modal.classList.add('animate-out');
        
        // Скрываем фон
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.classList.remove('show');
        }
        
        // Ждем завершения анимации
        setTimeout(() => {
            modal.classList.remove('show', 'animate-out');
            modal.style.display = 'none';
            
            // Разблокируем скролл
            this.unblockBodyScroll();
        }, 300);
        
        return true;
    }

    /**
     * Оптимизированная блокировка скролла страницы
     */
    blockBodyScroll() {
        if (this._scrollBlocked) return;
        
        // Запоминаем текущую позицию скролла
        this._savedScrollY = window.pageYOffset || document.documentElement.scrollTop;
        
        // Сохраняем оригинальные стили
        this._originalBodyStyles = {
            overflow: document.body.style.overflow,
            position: document.body.style.position,
            top: document.body.style.top,
            width: document.body.style.width
        };
        
        // Блокируем скролл
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${this._savedScrollY}px`;
        document.body.style.width = '100%';
        
        // Добавляем маркер-класс
        document.body.classList.add('modal-scroll-blocked');
        
        this._scrollBlocked = true;
    }
    
    /**
     * Оптимизированная разблокировка скролла страницы
     */
    unblockBodyScroll() {
        if (!this._scrollBlocked) return;
        
        // Полностью очищаем стили напрямую, без использования сохраненных значений
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.body.style.paddingRight = '';
        
        // Для гарантии полной очистки аттрибута style
        if (document.body.getAttribute('style') === '' || 
            document.body.getAttribute('style')?.includes('/*')) {
            document.body.removeAttribute('style');
        }
        
        // Удаляем маркер
        document.body.classList.remove('modal-scroll-blocked');
        
        // Восстанавливаем позицию скролла
        if (this._savedScrollY !== undefined) {
            window.scrollTo(0, this._savedScrollY);
        }
        
        this._scrollBlocked = false;
        
        // Очищаем сохраненные значения
        this._originalBodyStyles = undefined;
        this._savedScrollY = undefined;
    }
    
    /**
     * Очистка всех ресурсов при удалении компонента
     */
    destroy() {
        // Очистка обработчиков событий
        this._eventHandlers.forEach((typeHandlers, element) => {
            typeHandlers.forEach((handlers, eventType) => {
                handlers.forEach(handler => {
                    element.removeEventListener(eventType, handler);
                });
            });
        });
        this._eventHandlers.clear();
        
        // Отмена анимаций
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
        
        // Очистка таймаутов
        if (this.scrollTimeout) clearTimeout(this.scrollTimeout);
        if (this.debounceTimeout) clearTimeout(this.debounceTimeout);
        if (this.pageScrollTimeout) clearTimeout(this.pageScrollTimeout);
        if (this.hideNavigationTimeout) clearTimeout(this.hideNavigationTimeout);
        if (this.inactivityTimeout) clearTimeout(this.inactivityTimeout);
    }

    // Оставляем остальные методы без изменений для совместимости

    // Оптимизированный метод для настройки обнаружения скролла страницы
    setupPageScrollListener() {
        this._addSafeEventListener(window, 'scroll', () => {
            this.handlePageScroll();
        }, { passive: true });
        
        // Обработка touch событий для улучшения отзывчивости на мобильных
        this.setupTouchListeners();
        
        // Настройка обнаружения бездействия
        this.setupInactivityDetection();
        
        // Устанавливаем обработчики для регистрации активности пользователя
        this.setupUserActivityListeners();
    }
    
    // Обработка touch событий с учетом производительности
    setupTouchListeners() {
        let touchStart = null;
        let lastTouchMove = null;
        let touchDirection = null;
        let touchThrottleTimer = null;
        
        this._addSafeEventListener(document, 'touchstart', (e) => {
            touchStart = e.touches[0].clientY;
            touchDirection = null;
            
            // Обновляем время последнего действия пользователя
            this.registerUserActivity();
        }, { passive: true });
        
        // Используем throttling для touchmove для повышения производительности
        this._addSafeEventListener(document, 'touchmove', (e) => {
            if (touchThrottleTimer) return;
            
            touchThrottleTimer = setTimeout(() => {
                touchThrottleTimer = null;
                
                if (!touchStart) return;
                
                lastTouchMove = e.touches[0].clientY;
                const diff = touchStart - lastTouchMove;
                
                // Определяем направление свайпа только при значительном перемещении
                if (Math.abs(diff) > 10) {
                    // Свайп вниз
                    if (diff < 0 && (touchDirection !== 'down')) {
                        touchDirection = 'down';
                        this.showNavigation();
                    }
                    // Свайп вверх
                    else if (diff > 30 && (touchDirection !== 'up')) {
                        touchDirection = 'up';
                        if (!this.isInExcludedPath()) {
                            this.hideNavigation();
                        }
                    }
                }
            }, 100); // Throttle до 10 раз в секунду
        }, { passive: true });
        
        this._addSafeEventListener(document, 'touchend', () => {
            if (touchThrottleTimer) {
                clearTimeout(touchThrottleTimer);
                touchThrottleTimer = null;
            }
            
            touchStart = null;
            lastTouchMove = null;
            
            // Обновляем время последнего действия пользователя
            this.registerUserActivity();
        }, { passive: true });
    }
    
    // Проверка нахождения в исключенных путях (где нельзя скрывать навигацию)
    isInExcludedPath() {
        const currentPath = window.location.pathname;
        const excludedPaths = [
            '/templates/create-new/',
            '/templates/editor',
            '/client/templates/create-new/',
            '/client/templates/editor'
        ];
        
        return excludedPaths.some(path => currentPath.includes(path));
    }
    
    // Обработка скролла страницы - оптимизированная версия
    handlePageScroll() {
        const navigation = document.querySelector('.mb-navigation');
        if (!navigation) return;
        
        // Обновляем время последнего действия пользователя
        this.lastUserActionTime = Date.now();
        
        // Используем requestAnimationFrame для более плавной анимации
        if (this.pageScrollAnimationFrame) {
            cancelAnimationFrame(this.pageScrollAnimationFrame);
        }
        
        this.pageScrollAnimationFrame = requestAnimationFrame(() => {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            // Инициализируем lastPageScroll при первом вызове
            if (this.lastPageScroll === undefined || this.lastPageScroll === 0) {
                this.lastPageScroll = currentScroll;
                return; // Выходим, чтобы не вызвать ложное срабатывание
            }
            
            // Очищаем существующие таймауты
            if (this.pageScrollTimeout) {
                clearTimeout(this.pageScrollTimeout);
            }
            
            if (this.inactivityTimeout) {
                clearTimeout(this.inactivityTimeout);
            }
            
            // Проверяем исключенные пути перед скрытием навигации
            const canHideNavigation = !this.isInExcludedPath();
            
            // Вычисляем изменение значения скролла
            const scrollDelta = currentScroll - this.lastPageScroll;
            const isSignificantScroll = Math.abs(scrollDelta) > this.pageScrollThreshold;
            
            if (canHideNavigation && isSignificantScroll) {
                // Скролл вниз - скрываем навигацию
                if (scrollDelta > 0 && currentScroll > 50) {
                    this.hideNavigation();
                } 
                // Скролл вверх - показываем навигацию
                else if (scrollDelta < 0) {
                    this.showNavigation();
                }
            }
            
            // Устанавливаем таймаут для показа после паузы в прокрутке
            this.pageScrollTimeout = setTimeout(() => {
                if (Date.now() - this.lastUserActionTime >= this.inactivityThreshold) {
                    this.showNavigation();
                }
            }, this.inactivityThreshold);
            
            // Обновление настройки бездействия
            this.setupInactivityDetection();
            
            // Сохраняем текущую позицию скролла для следующего сравнения
            this.lastPageScroll = currentScroll;
        });
    }
    
    // Настройка обнаружения бездействия
    setupInactivityDetection() {
        if (this.inactivityTimeout) {
            clearTimeout(this.inactivityTimeout);
        }
        
        // Если навигация скрыта, показываем ее после периода бездействия
        this.inactivityTimeout = setTimeout(() => {
            if (this.isNavigationHidden) {
                this.showNavigation();
            }
        }, this.inactivityThreshold * 1.5); // Увеличиваем таймаут для снижения нагрузки
    }
    
    // Скрытие навигационной панели с проверкой допустимости
    hideNavigation() {
        const navigation = document.querySelector('.mb-navigation');
        if (!navigation || this.isNavigationHidden) return;
        
        // Проверка допустимости скрытия навигации на текущем пути
        if (this.isInExcludedPath()) {
            console.log('Скрытие навигации пропущено на странице редактора');
            return;
        }
        
        // Используем requestAnimationFrame для более плавной анимации
        requestAnimationFrame(() => {
            // Добавляем класс для скрытия
            navigation.classList.add('mb-nav-hidden');
            // Удаляем класс появления (если был)
            navigation.classList.remove('mb-nav-loaded');
            
            this.isNavigationHidden = true;
            
            // Сбрасываем существующие таймеры
            if (this.hideNavigationTimeout) {
                clearTimeout(this.hideNavigationTimeout);
            }
            
            // Отложенная настройка обнаружения бездействия
            this.hideNavigationTimeout = setTimeout(() => {
                this.setupInactivityDetection();
            }, 100);
        });
    }
    
    // Показ навигационной панели с оптимизированной анимацией
    showNavigation() {
        const navigation = document.querySelector('.mb-navigation');
        if (!navigation || !this.isNavigationHidden) return;
        
        if (this.hideNavigationTimeout) {
            clearTimeout(this.hideNavigationTimeout);
        }
        
        // Тактильная обратная связь на устройствах с поддержкой
        if (this.canUseVibrateAPI()) {
            navigator.vibrate(5);
        }
        
        // Используем плавную анимацию с requestAnimationFrame
        requestAnimationFrame(() => {
            // Удаляем класс скрытия
            navigation.classList.remove('mb-nav-hidden');
            
            this.isNavigationHidden = false;
            
            this.lastUserActionTime = Date.now();
            
            // Очищаем таймеры и запускаем новую проверку бездействия
            if (this.inactivityTimeout) {
                clearTimeout(this.inactivityTimeout);
            }
            
            this.setupInactivityDetection();
        });
    }
    
    // Проверка доступности Vibrate API с улучшенной проверкой взаимодействия
    canUseVibrateAPI() {
        return navigator.vibrate && 
               // Проверяем флаг взаимодействия из нашего класса
               this.userHasInteracted && 
               // Проверяем глобальный флаг взаимодействия пользователя
               window.userHasInteractedWithPage === true &&
               // Проверяем настройки уменьшения движения
               !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
    
    // Инициализация отслеживания взаимодействий пользователя
    initUserInteractionTracking() {
        // Единый обработчик для всех взаимодействий через делегирование событий
        const handler = () => {
            this.userHasInteracted = true;
            // Устанавливаем глобальный флаг взаимодействия пользователя
            window.userHasInteractedWithPage = true;
            
            // Используем immediatePropagation для гарантированного выполнения важных действий
            document.removeEventListener('click', handler, { capture: true });
            document.removeEventListener('touchstart', handler, { capture: true });
            document.removeEventListener('keydown', handler, { capture: true });
        };
        
        // Захватываем события на этапе capture для максимальной отзывчивости
        document.addEventListener('click', handler, { passive: true, capture: true });
        document.addEventListener('touchstart', handler, { passive: true, capture: true });
        document.addEventListener('keydown', handler, { passive: true, capture: true });
        
        // Информируем другие компоненты через глобальную переменную
        if (typeof window.userHasInteractedWithPage === 'undefined') {
            window.userHasInteractedWithPage = false;
        }
        
        // Используем настраиваемое свойство для более контролируемого доступа
        if (!Object.getOwnPropertyDescriptor(window, 'userHasInteractedWithPage')) {
            Object.defineProperty(window, 'userHasInteractedWithPage', {
                get: () => this.userHasInteracted,
                set: (value) => {
                    if (value === true) this.userHasInteracted = true;
                },
                configurable: false
            });
        }
    }
}
      