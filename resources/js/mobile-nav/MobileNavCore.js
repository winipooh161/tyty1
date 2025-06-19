export class MobileNavCore {
    constructor() {
        // Основные элементы
        this.container = null;
        this.iconsContainer = null;
        this.items = [];
        this.centerPoint = 0;
        this.sidePadding = 16; // Стандартный отступ по бокам
        
        // Состояние
        this.isInitialized = false;
        this.activeIconId = null;
        this.originalIcons = new Map(); // Для хранения оригинальных иконок
        
        // Инициализация после создания объекта
        this.init();
        
        // Инициализация с проверкой страницы редактора
        this.checkEditorPage();
        
        // Слушаем изменения URL
        window.addEventListener('popstate', () => this.checkEditorPage());
    }
    
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.findElements();
            });
        } else {
            // DOM уже загружен
            setTimeout(() => this.findElements(), 100);
        }
    }
    
    findElements() {
        // Находим контейнер для скролла
        this.container = document.getElementById('nav-scroll-container');
        
        // Находим контейнер с иконками
        this.iconsContainer = document.getElementById('nav-icons-container');
        
        // Проверяем, найдены ли все элементы
        if (!this.container || !this.iconsContainer) {
            console.warn('MobileNavCore: Не все элементы найдены, повторная попытка через 500ms');
            setTimeout(() => this.findElements(), 500);
            return;
        }
        
        // Находим все иконки
        this.items = Array.from(this.iconsContainer.querySelectorAll('.mb-icon-wrapper'));
        
        // Вычисляем центральную точку контейнера
        this.calculateCenterPoint();
        
        // Если есть иконки, инициализируем навигацию
        if (this.items.length > 0) {
            this.isInitialized = true;
            console.log('MobileNavCore: Навигация инициализирована');
        }
    }
    
    calculateCenterPoint() {
        // Центральная точка - середина контейнера
        this.centerPoint = this.container ? this.container.offsetWidth / 2 : 0;
    }
    
    // Скрытие навигационной панели
    hideNavigation() {
        const navigation = document.querySelector('.mb-navigation');
        if (navigation) {
            navigation.classList.add('mb-nav-hidden');
        }
    }
    
    // Показ навигационной панели
    showNavigation() {
        const navigation = document.querySelector('.mb-navigation');
        if (navigation) {
            navigation.classList.remove('mb-nav-hidden');
        }
    }
    
    // Преобразование иконки в кнопку "назад"
    convertIconToBackButton(iconId) {
        if (!this.isInitialized) return false;
        
        // Если у нас уже активна какая-то иконка, восстанавливаем её
        if (this.activeIconId && this.activeIconId !== iconId) {
            this.restoreIcon(this.activeIconId);
        }
        
        // Находим иконку по ID
        const iconElement = this.items.find(item => item.getAttribute('data-icon-id') === iconId);
        
        if (!iconElement) {
            console.warn(`MobileNavCore: Иконка с ID ${iconId} не найдена`);
            return false;
        }
        
        // Сохраняем оригинальное состояние иконки
        const iconLink = iconElement.querySelector('a');
        const iconImg = iconElement.querySelector('.mb-nav-icon');
        
        if (iconLink && iconImg) {
            // Сохраняем оригинальное содержимое, если еще не сохранено
            if (!this.originalIcons.has(iconId)) {
                this.originalIcons.set(iconId, {
                    link: iconLink.getAttribute('href'),
                    img: iconImg.getAttribute('src'),
                    classes: iconElement.className,
                    linkClasses: iconLink.className
                });
            }
            
            // Меняем на кнопку "назад"
            iconLink.setAttribute('href', 'javascript:void(0);');
            iconLink.classList.add('mb-nav-back-btn');
            iconImg.setAttribute('src', '/images/icons/arrow-left.svg');
            iconElement.classList.add('back-button-active');
            
            // Удаляем предыдущий обработчик события (если есть)
            if (iconLink._closeModalHandler) {
                iconLink.removeEventListener('click', iconLink._closeModalHandler);
            }
            
            // Создаем обработчик для закрытия модального окна
            iconLink._closeModalHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Нажата кнопка "Назад" для закрытия модального окна');
                
                if (window.modalPanel) {
                    window.modalPanel.closeModal();
                }
                return false;
            };
            
            // Добавляем обработчик для закрытия модального окна
            iconLink.addEventListener('click', iconLink._closeModalHandler);
            
            // Добавляем прямой обработчик onclick как запасной вариант
            iconLink.onclick = iconLink._closeModalHandler;
            
            // Устанавливаем активную иконку
            this.activeIconId = iconId;
            
            return true;
        }
        
        return false;
    }
    
    // Восстановление оригинальной иконки
    restoreIcon(iconId) {
        if (!this.isInitialized) return false;
        
        // Проверяем, сохранены ли оригинальные данные для этой иконки
        if (!this.originalIcons.has(iconId)) return false;
        
        // Находим иконку по ID
        const iconElement = this.items.find(item => item.getAttribute('data-icon-id') === iconId);
        
        if (!iconElement) {
            console.warn(`MobileNavCore: Иконка с ID ${iconId} для восстановления не найдена`);
            return false;
        }
        
        // Получаем оригинальные данные
        const originalData = this.originalIcons.get(iconId);
        
        // Находим элементы для восстановления
        const iconLink = iconElement.querySelector('a');
        const iconImg = iconElement.querySelector('.mb-nav-icon');
        
        if (iconLink && iconImg && originalData) {
            // Удаляем обработчик закрытия модального окна
            if (iconLink._closeModalHandler) {
                iconLink.removeEventListener('click', iconLink._closeModalHandler);
                delete iconLink._closeModalHandler;
            }
            
            // Восстанавливаем оригинальное состояние
            iconLink.setAttribute('href', originalData.link);
            iconLink.className = originalData.linkClasses;
            iconImg.setAttribute('src', originalData.img);
            iconElement.className = originalData.classes;
            
            // Удаляем прямой обработчик onclick
            iconLink.onclick = null;
            
            // Удаляем из сохраненных оригиналов
            this.originalIcons.delete(iconId);
            
            // Если это была активная иконка, сбрасываем активную иконку
            if (this.activeIconId === iconId) {
                this.activeIconId = null;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Проверяет, находимся ли на странице редактора и обеспечивает видимость навигации
     */
    checkEditorPage() {
        const currentUrl = window.location.href;
        const isEditorPage = /\/templates\/editor\/\d+/.test(currentUrl) || 
                            /\/client\/templates\/editor\/\d+/.test(currentUrl);
        
        if (isEditorPage) {
            this.ensureNavigationVisible();
            document.documentElement.classList.add('editor-page');
            
            // Для отладки
            console.log('Редактор обнаружен: ' + currentUrl);
        } else {
            document.documentElement.classList.remove('editor-page');
        }
    }
    
    /**
     * Обеспечивает видимость навигационной панели
     */
    ensureNavigationVisible() {
        const navElement = document.querySelector('.mb-navigation');
        if (navElement) {
            navElement.style.display = 'flex';
            navElement.classList.remove('mb-nav-hidden');
            navElement.classList.add('mb-nav-force-visible');
            
            // Для отладки
            console.log('Навигационная панель принудительно отображена');
        }
    }
}
