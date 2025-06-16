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
    }

    // Новый метод для безопасной настройки отслеживания времени скролла
    setupScrollTimeTracking() {
        // Используем DOMContentLoaded для безопасной инициализации
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
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
            this.core.container.addEventListener('scroll', () => {
                this.lastScrollTime = Date.now();
            }, { passive: true });
        } else {
            // Если контейнер всё еще не доступен, попробуем позже
            setTimeout(() => this.setupScrollListener(), 500);
        }
    }

    applyInertia(velocity) {
        if (!this.inertiaEnabled || Math.abs(velocity) < this.inertiaThreshold) return;
        
        this.momentumValue = velocity * 15; // Коэффициент для расчета инерции
        
        // Останавливаем предыдущую анимацию инерции, если она запущена
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
        }
        
        // Функция для анимации инерции
        const animateInertia = () => {
            // Применяем инерцию
            this.core.container.scrollLeft += this.momentumValue;
            
            // Уменьшаем момент с учетом фактора инерции
            this.momentumValue *= this.inertiaFactor;
            
            // Проверяем, нужно ли продолжать инерцию
            if (Math.abs(this.momentumValue) > this.inertiaThreshold) {
                this.rafId = requestAnimationFrame(animateInertia);
            } else {
                // Завершаем инерцию
                this.rafId = null;
            }
        };
        
        // Запускаем анимацию инерции
        this.rafId = requestAnimationFrame(animateInertia);
    }

    // Метод для получения максимального scrollLeft
    getMaxScrollLeft() {
        const containerWidth = this.core.container.offsetWidth;
        const scrollWidth = this.core.iconsContainer.scrollWidth;
        return Math.max(0, scrollWidth - containerWidth);
    }

    // Новый метод для инициализации отслеживания скролла страницы
    setupPageScrollListener() {
        window.addEventListener('scroll', () => {
            this.handlePageScroll();
        }, { passive: true });
        
        // Обработка touch событий для улучшения отзывчивости на мобильных
        this.setupTouchListeners();
    }
    
    // Обработка touch событий
    setupTouchListeners() {
        let touchStart = null;
        let touchMove = null;
        
        document.addEventListener('touchstart', (e) => {
            touchStart = e.touches[0].clientY;
        }, { passive: true });
        
        document.addEventListener('touchmove', (e) => {
            if (!touchStart) return;
            
            touchMove = e.touches[0].clientY;
            const diff = touchStart - touchMove;
            
            // Если свайп вниз (отрицательное значение)
            if (diff < -30) {
                this.showNavigation();
            }
            // Если свайп вверх (положительное значение)
            else if (diff > 30) {
                this.hideNavigation();
            }
        }, { passive: true });
        
        document.addEventListener('touchend', () => {
            touchStart = null;
            touchMove = null;
        }, { passive: true });
    }
    
    // Обработка скролла страницы
    handlePageScroll() {
        const navigation = document.querySelector('.mb-navigation');
        if (!navigation) return;
        
        // Обновляем время последнего действия пользователя
        this.lastUserActionTime = Date.now();
        
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        
        // Очищаем существующие таймауты
        if (this.pageScrollTimeout) {
            clearTimeout(this.pageScrollTimeout);
        }
        
        if (this.inactivityTimeout) {
            clearTimeout(this.inactivityTimeout);
        }
        
        // Скрываем навигацию при скролле вниз
        if (currentScroll > this.lastPageScroll && currentScroll > this.pageScrollThreshold) {
            this.hideNavigation();
        } 
        // Показываем навигацию при скролле вверх
        else if (currentScroll < this.lastPageScroll) {
            this.showNavigation();
        }
        
        // Устанавливаем таймаут для показа после паузы в прокрутке
        this.pageScrollTimeout = setTimeout(() => {
            // Показываем только если с момента последнего действия прошло достаточно времени
            if (Date.now() - this.lastUserActionTime >= this.inactivityThreshold) {
                this.showNavigation();
            }
        }, this.inactivityThreshold);
        
        // Всегда устанавливаем таймаут бездействия
        this.setupInactivityDetection();
        
        this.lastPageScroll = currentScroll;
    }
    
    // Новый метод для настройки обнаружения бездействия
    setupInactivityDetection() {
        if (this.inactivityTimeout) {
            clearTimeout(this.inactivityTimeout);
        }
        
        this.inactivityTimeout = setTimeout(() => {
            if (this.isNavigationHidden) {
                this.showNavigation();
            }
        }, this.inactivityThreshold);
    }
    
    // Скрытие навигационной панели
    hideNavigation() {
        const navigation = document.querySelector('.mb-navigation');
        if (!navigation || this.isNavigationHidden) return;
        
        // Применяем RAF для более плавной анимации
        requestAnimationFrame(() => {
            navigation.classList.add('mb-nav-hidden');
            this.isNavigationHidden = true;
            
            // Обновляем время последнего действия пользователя
            this.lastUserActionTime = Date.now();
            
            // Очищаем таймаут показа, если он был установлен
            if (this.hideNavigationTimeout) {
                clearTimeout(this.hideNavigationTimeout);
                this.hideNavigationTimeout = null;
            }
            
            // Устанавливаем детекцию бездействия с небольшой задержкой
            setTimeout(() => {
                this.setupInactivityDetection();
            }, 100);
        });
    }
    
    // Улучшенный метод для показа навигации
    showNavigation() {
        const navigation = document.querySelector('.mb-navigation');
        if (!navigation || !this.isNavigationHidden) return;
        
        // Убираем настроенный таймаут, если он существует
        if (this.hideNavigationTimeout) {
            clearTimeout(this.hideNavigationTimeout);
            this.hideNavigationTimeout = null;
        }
        
        // Тактильная обратная связь при появлении панели
        if (navigator.vibrate && this.isNavigationHidden) {
            navigator.vibrate(5);
        }
        
        // Применяем RAF для более плавной анимации с отложенным добавлением класса
        requestAnimationFrame(() => {
            // Используем setTimeout для добавления небольшой задержки перед анимацией
            setTimeout(() => {
                navigation.classList.remove('mb-nav-hidden');
                this.isNavigationHidden = false;
                
                // Обновляем время последнего действия пользователя
                this.lastUserActionTime = Date.now();
                
                // Очищаем таймаут бездействия, так как мы только что выполнили действие
                if (this.inactivityTimeout) {
                    clearTimeout(this.inactivityTimeout);
                    this.inactivityTimeout = null;
                }
                
                // Запускаем новый таймер для скрытия после периода бездействия
                this.setupInactivityDetection();
            }, 50);
        });
    }
    
    // Метод для обнаружения активности пользователя и сброса таймеров
    registerUserActivity() {
        this.lastUserActionTime = Date.now();
        
        // Перезапускаем таймер бездействия
        this.setupInactivityDetection();
    }
    
    // Метод для установки обработчиков активности пользователя
    setupUserActivityListeners() {
        // События, которые свидетельствуют об активности пользователя
        const activityEvents = ['touchstart', 'touchmove', 'mousemove', 'click', 'keydown', 'wheel'];
        
        // Устанавливаем обработчики для каждого типа события
        activityEvents.forEach(eventType => {
            document.addEventListener(eventType, () => {
                this.registerUserActivity();
            }, { passive: true });
        });
    }

    /**
     * Метод для определения, доступно ли использование Vibrate API
     */
    canUseVibrateAPI() {
        return navigator.vibrate && this.userHasInteracted && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
    
    /**
     * Инициализация системы отслеживания взаимодействий
     */
    initUserInteractionTracking() {
        const interactionEvents = ['click', 'touchstart', 'touchmove', 'mousedown', 'keydown'];
        
        const setUserInteracted = () => {
            this.userHasInteracted = true;
            // Удаляем обработчики после первого взаимодействия
            interactionEvents.forEach(event => {
                document.removeEventListener(event, setUserInteracted, { passive: true });
            });
        };
        
        // Добавляем слушатели событий для определения взаимодействия
        interactionEvents.forEach(event => {
            document.addEventListener(event, setUserInteracted, { passive: true });
        });
    }
}
