export class MobileNavPopup {
    constructor(core) {
        this.core = core;
        this.isPopupOpen = false;
        this.currentPopupConfig = null;
        this.popupContainer = null;
        this.backdrop = null;
        this.swipeStartY = 0;
        this.swipeStartX = 0;
        this.isSwipeDetected = false;
        this.minSwipeDistance = 50;
        this.isUpSwipeInProgress = false;
        
        // Теперь вместо предопределенных конфигураций будем загружать из HTML
        this.popupConfigs = {};
        
        // Добавляем флаг для отслеживания взаимодействия пользователя
        this.userHasInteracted = false;
        
        // Добавляем новые переменные для отслеживания иконки свайпа
        this.swipeTargetElement = null;
        this.swipeTargetIconId = null;
        
        // Добавляем переменные для хранения модальных триггеров
        this.modalTriggers = new Map();

        // Добавляем переменные для управления скроллом
        this.originalBodyOverflow = '';
        this.originalBodyPosition = '';
        this.scrollY = 0;

        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.delayedInit();
            });
        } else {
            this.delayedInit();
        }
    }

    delayedInit() {
        setTimeout(() => {
            this.createPopupElements();
            this.loadPopupConfigsFromHtml();
            this.setupSwipeDetection();
        }, 500);
    }
    
    // Новый метод для загрузки конфигурации из HTML
    loadPopupConfigsFromHtml() {
        const popupConfigElement = document.getElementById('mobile-nav-popup-configs');
        
        // Если элемент с конфигурациями не найден, используем запасной вариант
        if (!popupConfigElement) {
            console.warn('Элемент #mobile-nav-popup-configs не найден, используются запасные конфигурации');
            this.popupConfigs = this.getFallbackPopupConfigs();
            return;
        }
        
        // Найти все секции конфигураций по атрибуту data-popup-config
        const configSections = popupConfigElement.querySelectorAll('[data-popup-config]');
        
        configSections.forEach(section => {
            const configId = section.getAttribute('data-popup-config');
            const title = section.querySelector('.popup-config-title')?.textContent || '';
            const items = [];
            
            // Собираем все элементы из этой секции
            const itemElements = section.querySelectorAll('.popup-item');
            
            itemElements.forEach(item => {
                const iconSrc = item.getAttribute('data-icon');
                const href = item.getAttribute('data-href') || '#';
                
                // Проверяем, открывает ли этот элемент модальное окно
                const isModal = item.getAttribute('data-modal') === 'true';
                const modalId = item.getAttribute('data-modal-target') || null;
                const itemTitle = item.getAttribute('data-title') || '';
                
                items.push({
                    icon: iconSrc,
                    href: href,
                    title: itemTitle,
                    isModal: isModal,
                    modalId: modalId
                });
            });
            
            // Сохраняем конфигурацию для этого ID
            if (items.length > 0) {
                this.popupConfigs[configId] = {
                    title: title,
                    items: items
                };
            }
        });
        
        // Если ничего не загружено, используем запасной вариант
        if (Object.keys(this.popupConfigs).length === 0) {
            this.popupConfigs = this.getFallbackPopupConfigs();
        }
    }

    // Запасной вариант конфигурации, если HTML не найден
    getFallbackPopupConfigs() {
        return {
            'home': {
                title: 'Главная',
                items: [
                    { icon: 'newspaper.svg', href: '/news', isModal: false, title: 'Новости' },
                    { icon: 'calendar.svg', href: '/events', isModal: false, title: 'События' },
                    { icon: 'info-circle.svg', href: '/about', isModal: false, title: 'О нас' }
                ]
            },
            'profile': {
                title: 'Профиль',
                items: [
                    { icon: 'gear.svg', href: '/profile/settings', isModal: false, title: 'Настройки' },
                    { icon: 'clock-history.svg', href: '/user/templates', isModal: false, title: 'История' },
                    { icon: 'heart.svg', href: '/user/favorites', isModal: false, title: 'Избранное' }
                ]
            },
            'create': {
                title: 'Создать',
                items: [
                    { icon: 'file-earmark.svg', href: '/client/templates/categories', isModal: false, title: 'Шаблоны' },
                    { icon: 'folder-plus.svg', href: '/client/projects', isModal: false, title: 'Проекты' },
                    { icon: 'image.svg', href: '/client/images', isModal: false, title: 'Изображения' }
                ]
            },
            'games': {
                title: 'Развлечения',
                items: [
                    { icon: 'puzzle.svg', href: '/games/puzzle', isModal: false, title: 'Пазлы' },
                    { icon: 'controller.svg', href: '/games/arcade', isModal: false, title: 'Аркады' },
                    { icon: 'trophy.svg', href: '/games/tournaments', isModal: false, title: 'Турниры' }
                ]
            },
            'email': {
                title: 'Почта',
                items: [
                    { icon: 'inbox.svg', href: '/email/inbox', isModal: false, title: 'Входящие' },
                    { icon: 'send.svg', href: '/email/sent', isModal: false, title: 'Отправленные' },
                    { icon: 'pencil.svg', href: '/email/compose', isModal: false, title: 'Написать' }
                ]
            },
            'admin': {
                title: 'Администрирование',
                items: [
                    { icon: 'people.svg', href: '/admin/users', isModal: false, title: 'Пользователи' },
                    { icon: 'bar-chart.svg', href: '/admin/statistics', isModal: false, title: 'Статистика' },
                    { icon: 'gear.svg', href: '/admin/settings', isModal: false, title: 'Настройки' }
                ]
            },
            'qr-scanner': {
                title: 'QR Сканер',
                items: [
                    { icon: 'qr-code.svg', href: '#', isModal: true, modalId: 'qr-scanner-modal', title: 'Сканировать' },
                    { icon: 'camera.svg', href: '#', isModal: true, modalId: 'camera-modal', title: 'Камера' },
                    { icon: 'image.svg', href: '/qr/history', isModal: false, title: 'История' }
                ]
            }
        };
    }

    createPopupElements() {
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'mb-popup-backdrop';
        
        this.popupContainer = document.createElement('div');
        this.popupContainer.className = 'mb-popup-container mb-popup-swipeable'; // Добавляем класс для стилизации

        // Добавляем индикатор свайпа
        const swipeIndicator = document.createElement('div');
        swipeIndicator.className = 'mb-swipe-indicator';
        this.popupContainer.appendChild(swipeIndicator);

        document.body.appendChild(this.backdrop);
        document.body.appendChild(this.popupContainer);

        this.backdrop.addEventListener('click', () => this.closePopup());
        
        this.popupContainer.addEventListener('touchstart', (e) => {
            this.swipeStartY = e.touches[0].clientY;
        });

        this.popupContainer.addEventListener('touchend', (e) => {
            const swipeEndY = e.changedTouches[0].clientY;
            const swipeDistance = swipeEndY - this.swipeStartY;
            
            if (swipeDistance > this.minSwipeDistance) {
                this.closePopup();
            }
        });
    }

    setupSwipeDetection() {
        if (!this.core.container) {
            const container = document.getElementById('nav-scroll-container');
            if (container) {
                this.core.container = container;
            } else {
                return;
            }
        }

        this.core.container.addEventListener('touchstart', (e) => {
            // Устанавливаем флаг взаимодействия пользователя
            this.userHasInteracted = true;
            
            // Вместо проверки только центрированного элемента,
            // определяем, над какой иконкой находится палец
            const touch = e.touches[0];
            const touchX = touch.clientX;
            const touchY = touch.clientY;
            
            // Получаем все иконки в контейнере
            const allIcons = Array.from(this.core.iconsContainer.querySelectorAll('.mb-icon-wrapper'));
            
            // Находим иконку под пальцем пользователя
            let targetIcon = null;
            for (const icon of allIcons) {
                const rect = icon.getBoundingClientRect();
                if (
                    touchX >= rect.left && 
                    touchX <= rect.right && 
                    touchY >= rect.top && 
                    touchY <= rect.bottom
                ) {
                    targetIcon = icon;
                    break;
                }
            }
            
            // Если иконка найдена, сохраняем ее данные
            if (targetIcon) {
                this.swipeTargetElement = targetIcon;
                this.swipeTargetIconId = targetIcon.getAttribute('data-icon-id');
                this.swipeStartY = touchY;
                this.swipeStartX = touchX;
                this.isSwipeDetected = false;
                this.isUpSwipeInProgress = false;
                
                // Добавляем визуальную обратную связь при касании
                targetIcon.classList.add('mb-touch-active');
            } else {
                // Сбрасываем данные, если касание не попало на иконку
                this.swipeTargetElement = null;
                this.swipeTargetIconId = null;
                this.swipeStartY = 0;
                this.swipeStartX = 0;
            }
        }, { passive: true });

        this.core.container.addEventListener('touchmove', (e) => {
            // Если нет активной иконки для свайпа, выходим
            if (!this.swipeTargetElement || this.swipeStartY === 0) return;

            const touch = e.touches[0];
            const deltaY = this.swipeStartY - touch.clientY;
            const deltaX = Math.abs(touch.clientX - this.swipeStartX);

            // Проверяем свайп вверх с более точными параметрами
            if (deltaY > 15 && deltaX < 40) { // Уменьшаем порог для более легкого определения
                this.isUpSwipeInProgress = true;
                
                // Показываем визуальную подсказку о свайпе вверх
                if (!this.swipeTargetElement.classList.contains('swiping-up')) {
                    this.swipeTargetElement.classList.add('swiping-up');
                }
                
                // Блокируем скролл контейнера только при значительном движении вверх
                if (deltaY > 25) {
                    this.core.container.style.overflowX = 'hidden';
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                // Если свайп достаточно длинный, отмечаем его как обнаруженный
                if (deltaY > this.minSwipeDistance) {
                    this.isSwipeDetected = true;
                    
                    // Вибрация для тактильной обратной связи
                    if (navigator.vibrate && this.userHasInteracted && 
                        !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        try {
                            navigator.vibrate(20);
                        } catch (error) {
                            // Игнорируем ошибки vibrate API
                        }
                    }
                }
            }
        }, { passive: false });

        this.core.container.addEventListener('touchend', (e) => {
            // Восстанавливаем скролл контейнера
            this.core.container.style.overflowX = 'auto';
            
            // Убираем класс активного касания со всех элементов
            if (this.swipeTargetElement) {
                this.swipeTargetElement.classList.remove('mb-touch-active', 'swiping-up');
            }
            
            // Если был обнаружен свайп вверх и есть целевая иконка
            if (this.isSwipeDetected && this.swipeTargetIconId) {
                console.log(`Свайп вверх обнаружен на иконке: ${this.swipeTargetIconId}`);
                
                // Показываем попап для конкретной иконки
                this.showPopup(this.swipeTargetIconId);
            }
            
            // Сброс состояния
            this.swipeTargetElement = null;
            this.swipeStartY = 0;
            this.swipeStartX = 0;
            this.isSwipeDetected = false;
            this.isUpSwipeInProgress = false;
            this.swipeTargetIconId = null;
        });
    }

    getCenteredItem() {
        // Функция оставлена для совместимости
        return null;
    }

    showPopup(iconId) {
        const config = this.popupConfigs[iconId];
        if (!config || this.isPopupOpen) return;

        this.currentPopupConfig = config;
        this.isPopupOpen = true;
        
        // Блокируем скролл body перед показом попапа
        this.blockBodyScroll();
        
        // Сохраняем ID иконки, на которой был сделан свайп
        // Это значение необходимо для связывания с модальным окном
        this.currentIconId = iconId;

        // Добавляем заголовок иконки к popup
        this.renderPopupContent(config, iconId);

        requestAnimationFrame(() => {
            this.backdrop.style.opacity = '1';
            this.backdrop.style.visibility = 'visible';
            
            this.popupContainer.style.opacity = '1';
            this.popupContainer.style.visibility = 'visible';
            this.popupContainer.style.transform = 'translateX(-50%) translateY(0)';
        });

        // Вызываем вибрацию только если есть доступ к API
        if (navigator.vibrate && this.userHasInteracted && 
            !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            try {
                navigator.vibrate(50);
            } catch (error) {
                // Игнорируем ошибки вызова vibrate
            }
        }
    }

    renderPopupContent(config, iconId) {
        // Добавляем класс, соответствующий иконке
        this.popupContainer.className = 'mb-popup-container mb-popup-swipeable';
        this.popupContainer.classList.add(`popup-for-${iconId}`);
        
        // Формируем HTML с заголовком иконки
        const iconTitle = this.getIconTitle(iconId);
        
        this.popupContainer.innerHTML = `
            <div class="mb-swipe-indicator"></div>
           
            <div class="mb-popup-grid">
                ${config.items.map((item, index) => {
                    // Определяем атрибуты в зависимости от типа элемента (модальное окно или ссылка)
                    const actionAttrs = item.isModal 
                        ? `href="javascript:void(0);" data-modal="true" data-modal-target="${item.modalId}" class="mb-popup-item modal-trigger no-spinner"`
                        : `href="${item.href}" class="mb-popup-item"`;
                    
                    return `
                        <a ${actionAttrs} style="animation-delay: ${index * 0.1}s;">
                            <img src="/images/icons/${item.icon}" 
                                alt="${item.title}" 
                                title="${item.title}"
                                onerror="this.src='/images/icons/placeholder.svg'; this.classList.add('fallback-icon');"
                                onload="this.classList.add('loaded-icon');">
                            <span class="popup-item-title">${item.title}</span>
                        </a>
                    `;
                }).join('')}
            </div>
        `;

        this.ensureAnimationStyles();
        this.setupPopupEventListeners();
    }
    
    // Новый метод для получения заголовка иконки
    getIconTitle(iconId) {
        // Сначала проверяем конфигурацию попапа
        if (this.popupConfigs[iconId] && this.popupConfigs[iconId].title) {
            return this.popupConfigs[iconId].title;
        }
        
        // Если заголовок не найден в конфигурации, ищем в DOM
        const iconElement = this.core.iconsContainer.querySelector(`[data-icon-id="${iconId}"]`);
        if (iconElement) {
            // Пытаемся найти подпись под иконкой или alt у изображения
            const imgElement = iconElement.querySelector('img');
            if (imgElement && imgElement.alt) {
                return imgElement.alt;
            }
            
            // Проверяем атрибут title у ссылки
            const linkElement = iconElement.querySelector('a');
            if (linkElement && linkElement.title) {
                return linkElement.title;
            }
        }
        
        // Если всё еще не нашли заголовок, используем ID с первой заглавной буквой
        if (iconId) {
            return iconId.charAt(0).toUpperCase() + iconId.slice(1);
        }
        
        return '';
    }

    setupPopupEventListeners() {
        // Кнопки закрытия больше нет, поэтому добавляем закрытие по тапу за пределами сетки
        this.popupContainer.addEventListener('click', (e) => {
            // Если клик был вне сетки элементов (.mb-popup-grid), закрываем попап
            if (!e.target.closest('.mb-popup-grid')) {
                e.preventDefault();
                this.closePopup();
            }
        });

        const popupItems = this.popupContainer.querySelectorAll('.mb-popup-item');
        
        popupItems.forEach((item) => {
            // Если это модальное окно
            if (item.hasAttribute('data-modal-target')) {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const modalId = item.getAttribute('data-modal-target');
                    
                    // Используем сохраненный ID иконки вместо swipeTargetIconId
                    console.log('Сохраняем связь для модалки', modalId, 'с иконкой', this.currentIconId);
                    
                    // Запоминаем текущий элемент свайпа для возможности смены иконки
                    this.modalTriggers.set(modalId, {
                        element: this.swipeTargetElement,
                        iconId: this.currentIconId // Используем сохраненное значение
                    });
                    
                    this.closePopup();
                    
                    // Небольшая задержка перед открытием модального окна
                    setTimeout(() => {
                        if (window.openModalPanel) {
                            window.openModalPanel(modalId);
                        }
                    }, 300);
                });
            } else {
                // Для обычных ссылок просто закрываем попап
                item.addEventListener('click', () => {
                    this.closePopup();
                });
            }
        });
    }

    ensureAnimationStyles() {
        if (!document.querySelector('#mb-popup-animations')) {
            const style = document.createElement('style');
            style.id = 'mb-popup-animations';
            style.textContent = `
                .mb-popup-item {
                      transform: translateY(20px);
    opacity: 0;
    animation: slideUpFade 0.3s ease forwards;
    display: flex
;
    flex-direction: row;
    align-items: center;
    gap: 5px;
    align-content: center;
    justify-content: flex-start;
                }
                .popup-item-title {
                   font-size: 16px;
    text-align: center;
    color: #333;
    margin-top: 0;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
                }
                @keyframes slideUpFade {
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                .mb-popup-item .fallback-icon {
                    opacity: 0.4;
                    filter: grayscale(1);
                }
                .mb-popup-item .loaded-icon {
                    opacity: 1;
                    filter: none;
                }
                .mb-popup-item img {
                    transition: all 0.3s ease;
                    width: 32px;
                    height: 32px;
                }
                
                /* Стили для класса mb-touch-active и swiping-up */
                .mb-icon-wrapper.mb-touch-active {
                    opacity: 0.8;
                    transform: scale(0.95);
                    transition: all 0.2s ease;
                }
                
                .mb-icon-wrapper.swiping-up {
                    transform: scale(0.92);
                }
                
                .mb-icon-wrapper.swiping-up::after {
                    content: '';
                    position: absolute;
                    top: -8px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 0;
                    height: 0;
                    border-left: 6px solid transparent;
                    border-right: 6px solid transparent;
                    border-bottom: 8px solid rgba(0, 123, 255, 0.6);
                    animation: pulse 1s infinite;
                }
                
                @keyframes pulse {
                    0% { opacity: 0.4; }
                    50% { opacity: 1; }
                    100% { opacity: 0.4; }
                }
                
                /* Стили для заголовка попапа */
                .mb-popup-title {
                    margin: 0;
                    padding: 12px 20px 0;
                    text-align: center;
                    font-weight: 600;
                    font-size: 1rem;
                    color: #333;
                }
                
                /* Стили для кнопки "назад" */
                .mb-icon-wrapper.back-button-active {
                    position: relative;
                    transform: scale(1.05);
                    transition: all 0.3s ease;
                }
                
                .mb-icon-wrapper.back-button-active::after {
                    content: '';
                    position: absolute;
                    bottom: -8px;
                    left: 50%;
                    width: 6px;
                    height: 6px;
                    background-color: #007bff;
                    border-radius: 50%;
                    transform: translateX(-50%);
                    animation: pulse 1.5s infinite;
                }
                
                .mb-icon-wrapper.back-button-active .mb-nav-icon {
                    filter: brightness(1.2);
                    animation: backButtonPulse 2s infinite;
                }
                
                @keyframes backButtonPulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
            `;
            document.head.appendChild(style);
        }
    }

    closePopup() {
        if (!this.isPopupOpen) return;

        this.backdrop.style.opacity = '0';
        this.backdrop.style.visibility = 'hidden';
        
        this.popupContainer.style.opacity = '0';
        this.popupContainer.style.transform = 'translateX(-50%) translateY(100px)';

        setTimeout(() => {
            this.popupContainer.style.visibility = 'hidden';
            this.isPopupOpen = false;
            this.currentPopupConfig = null;
            
            // Убеждаемся, что активная иконка восстановлена
            // если это не иконка, активно используемая в модальном окне
            if (this.currentIconId && 
                !document.querySelector('.modal-panel.show') &&
                window.MobileNavWheelPicker &&
                window.MobileNavWheelPicker.core) {
                
                window.MobileNavWheelPicker.core.restoreIcon(this.currentIconId);
                this.currentIconId = null;
            }
            
            // Разблокируем скролл body после закрытия попапа
            this.unblockBodyScroll();
        }, 400);
    }

    // Улучшенный метод для блокировки скролла body с проверкой текущего пути
    blockBodyScroll() {
        // Проверяем путь - на страницах редактора не блокируем скролл
        const currentPath = window.location.pathname;
        if (currentPath.includes('/templates/create-new/') || 
            currentPath.includes('/templates/editor') || 
            currentPath.includes('/client/templates/create-new/') || 
            currentPath.includes('/client/templates/editor')) {
            console.log('Блокировка скролла пропущена на странице редактора');
            return;
        }
        
        // Сохраняем текущее положение скролла
        this.scrollY = window.pageYOffset || document.documentElement.scrollTop;
        
        // Сохраняем оригинальные стили body
        this.originalBodyOverflow = document.body.style.overflow;
        this.originalBodyPosition = document.body.style.position;
        
        // Применяем стили для блокировки скролла
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${this.scrollY}px`;
        document.body.style.width = '100%';
        
        // Добавляем класс для дополнительной стилизации
        document.body.classList.add('popup-scroll-blocked');
    }

    // Улучшенный метод для разблокировки скролла body
    unblockBodyScroll() {
        // Проверяем, был ли скролл заблокирован
        if (!document.body.classList.contains('popup-scroll-blocked')) {
            return;
        }
        
        // Восстанавливаем оригинальные стили
        document.body.style.overflow = this.originalBodyOverflow;
        document.body.style.position = this.originalBodyPosition;
        document.body.style.top = '';
        document.body.style.width = '';
        
        // Убираем класс
        document.body.classList.remove('popup-scroll-blocked');
        
        // Восстанавливаем позицию скролла
        if (this.scrollY > 0) {
            window.scrollTo(0, this.scrollY);
        }
        
        // Сбрасываем сохраненные значения
        this.scrollY = 0;
        this.originalBodyOverflow = '';
        this.originalBodyPosition = '';
    }

    // Метод для интеграции с событиями скролла навигации
    blockHorizontalScroll() {
        return this.isUpSwipeInProgress;
    }
}
