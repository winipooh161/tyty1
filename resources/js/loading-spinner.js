class LoadingSpinner {
    constructor() {
        this.overlay = null;
        this.isVisible = false;
        this.hideTimeout = null;
        this.minDisplayTime = 2000; // Минимальное время отображения (2 сек для вращения)
        this.showStartTime = null;
        this.isBlocked = false; // Новый флаг для блокировки показа спиннера
        this.blockTimeout = null; // Таймаут для разблокировки
        
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.overlay = document.getElementById('globalLoadingSpinner');
            this.setupEventListeners();
            this.bindToNavigationAndForms();
        });
    }

    setupEventListeners() {
        // Убираем автоматическое отображение при загрузке страницы
        
        // Показываем спиннер при переходах по ссылкам с дополнительной проверкой
        document.addEventListener('click', (e) => {
            // Немедленно выходим, если спиннер заблокирован
            if (this.isBlocked) {
                return;
            }
            
            // Проверяем, не является ли клик модальным событием
            if (this.isModalEvent(e) || document.body.classList.contains('modal-active')) {
                return;
            }
            
            const link = e.target.closest('a[href]');
            if (link && this.shouldShowSpinnerForLink(link)) {
                this.show('Переход');
            }
        });

        // Обработка событий popstate (навигация назад/вперед)
        window.addEventListener('popstate', () => {
            this.show('Загрузка');
        });
    }

    bindToNavigationAndForms() {
        // Привязываем к мобильной навигации
        const mobileNavLinks = document.querySelectorAll('.mb-nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.show('Загрузка');
            });
        });

        // Привязываем к боковой навигации
        const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.show('Переход');
            });
        });

        // Привязываем к формам
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.shouldShowSpinnerForForm(form)) return;
                this.show('Обработка');
            });
        });
    }

    shouldShowSpinnerForLink(link) {
        // Если спиннер заблокирован, не показываем его
        if (this.isBlocked) return false;
        
        const href = link.getAttribute('href');
        
        // Не показываем для якорных ссылок
        if (!href || href.startsWith('#')) return false;
        
        // Не показываем для javascript:void(0) ссылок
        if (href.includes('javascript:void(0)') || href === '#') return false;
        
        // Расширенная проверка модальных элементов
        if (link.classList.contains('no-spinner') || 
            link.closest('.no-spinner') || 
            link.closest('[data-modal="true"]') || 
            link.closest('.modal-trigger')) {
            return false;
        }
        
        // Проверка на наличие родительских элементов с атрибутами модального окна
        const parent = link.parentElement;
        if (parent && (
            parent.hasAttribute('data-modal') || 
            parent.hasAttribute('data-modal-target') ||
            parent.classList.contains('modal-trigger')
        )) {
            return false;
        }
        
        // Не показываем для внешних ссылок
        if (href.includes('http') && !href.includes(window.location.hostname)) return false;
        
        // Не показываем для ссылок с target="_blank"
        if (link.getAttribute('target') === '_blank') return false;
        
        // Дополнительная проверка на модальные окна
        if (document.body.classList.contains('modal-active')) return false;
        
        return true;
    }

    shouldShowSpinnerForForm(form) {
        // Не показываем для форм с классом no-spinner
        if (form.classList.contains('no-spinner')) return false;
        
        // Не показываем для AJAX форм
        if (form.classList.contains('ajax-form')) return false;
        
        return true;
    }

    show(text = 'Загрузка') {
        if (this.isVisible) return;
        
        this.isVisible = true;
        this.showStartTime = Date.now();
        
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }

        if (this.overlay) {
            // Обновляем текст загрузки
            const loadingText = this.overlay.querySelector('.loading-text');
            if (loadingText) {
                loadingText.innerHTML = `${text}<span class="loading-dots">...</span>`;
            }
            
            // Показываем спиннер
            this.overlay.classList.add('show');
            
            // Блокируем скролл страницы
            document.body.style.overflow = 'hidden';
            
            // Добавляем вибрацию на мобильных устройствах
            if (navigator.vibrate) {
                navigator.vibrate(30);
            }
        }
    }

    hide() {
        if (!this.isVisible) return;
        
        const elapsed = Date.now() - (this.showStartTime || 0);
        const remainingTime = Math.max(0, this.minDisplayTime - elapsed);
        
        // Если спиннер отображался меньше минимального времени (1 сек), ждем
        if (remainingTime > 0) {
            this.hideTimeout = setTimeout(() => {
                this.performHide();
            }, remainingTime);
        } else {
            this.performHide();
        }
    }

    performHide() {
        this.isVisible = false;
        this.showStartTime = null;
        
        if (this.overlay) {
            // Убираем класс show, запуская медленное исчезновение (500мс)
            this.overlay.classList.remove('show');
            
            // Восстанавливаем скролл страницы после полного исчезновения
            setTimeout(() => {
                document.body.style.overflow = '';
            }, 1500); // 500мс - время анимации исчезновения
        }
        
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }
    }

    // Публичные методы для использования в других частях приложения
    showWithText(text) {
        this.show(text);
    }

    // Метод для показа спиннера с кастомным временем
    showFor(duration, text = 'Загрузка') {
        this.show(text);
        setTimeout(() => {
            this.hide();
        }, duration);
    }

    // Добавляем метод для временной блокировки показа спиннера
    blockShowTemporarily(duration = 1000) {
        this.isBlocked = true;
        
        // Очищаем предыдущий таймаут, если он был
        if (this.blockTimeout) {
            clearTimeout(this.blockTimeout);
        }
        
        // Устанавливаем таймер для сброса блокировки
        this.blockTimeout = setTimeout(() => {
            this.isBlocked = false;
        }, duration);
    }

    // Улучшенный метод для принудительного скрытия
    forceHide() {
        // Блокируем показ спиннера на короткое время
        this.blockShowTemporarily(500);
        
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }
        
        this.performHide();
    }

    // Улучшенная проверка модальных событий
    isModalEvent(event) {
        // Если тело документа имеет класс modal-active, считаем все события модальными
        if (document.body.classList.contains('modal-active')) {
            return true;
        }
        
        const target = event.target;
        
        // Проверяем, есть ли атрибут data-modal="true" или класс modal-trigger
        if (target.closest('[data-modal="true"]') || 
            target.closest('.modal-trigger') ||
            target.closest('.modal-panel') ||
            target.closest('.mb-popup-container')) {
            return true;
        }
        
        // Проверяем, есть ли на ссылке или её родителях класс no-spinner
        if (target.closest('.no-spinner')) {
            return true;
        }
        
        return false;
    }
}

// Создаем глобальный экземпляр спиннера
window.loadingSpinner = new LoadingSpinner();

// Экспортируем для модульного использования
export default LoadingSpinner;
