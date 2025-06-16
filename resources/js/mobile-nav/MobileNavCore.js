export class MobileNavCore {
    constructor() {
        // DOM-элементы
        this.navigation = null; // Контейнер навигации
        this.container = null; // Контейнер скроллера
        this.iconsContainer = null; // Контейнер с иконками
        this.items = []; // Массив элементов навигации
        this.initialActiveItem = null; // Исходно активный элемент
        
        // Настройки
        this.sidePadding = 20; // Отступы с обеих сторон
        
        // Состояние
        this.isInitialized = false; // Флаг инициализации
        this.isLoading = true; // Флаг загрузки
        this.originalItems = new Map(); // Сохранение оригинальных иконок для восстановления

        // Инициализация
        this.init();
    }

    // Ленивая инициализация навигации
    init() {
        if (this.isInitialized) return;

        // Используем DOMContentLoaded для безопасной инициализации
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupNavigation();
            });
        } else {
            // DOM уже загружен, инициализируем сейчас
            this.setupNavigation();
        }
    }

    // Настройка навигации
    setupNavigation() {
        // Находим основные DOM-элементы
        this.navigation = document.querySelector('.mb-navigation');
        if (!this.navigation) {
            console.warn('MobileNavCore: Элемент навигации не найден в DOM');
            return;
        }

        this.container = document.getElementById('nav-scroll-container');
        this.iconsContainer = document.getElementById('nav-icons-container');

        if (!this.container || !this.iconsContainer) {
            console.warn('MobileNavCore: Не найдены необходимые контейнеры для навигации');
            return;
        }

        // Получаем все элементы навигации
        this.items = Array.from(this.iconsContainer.querySelectorAll('.mb-icon-wrapper'));
        
        // Если элементы есть, инициализируем навигацию
        if (this.items.length > 0) {
            this.setupItems();
            this.isInitialized = true;
            this.showNavigation();
            
            // Инициализация завершена, снимаем флаг загрузки
            setTimeout(() => {
                this.isLoading = false;
            }, 300);
        } else {
            console.warn('MobileNavCore: Не найдены элементы навигации');
        }
    }

    // Настройка элементов навигации
    setupItems() {
        // Проходим по всем элементам и настраиваем их
        this.items.forEach((item, index) => {
            // Задаем CSS-переменную для задержки анимации
            item.style.setProperty('--item-index', index);
            
            // Сохраняем оригинальное содержимое для возможности восстановления
            const itemId = item.getAttribute('data-icon-id');
            if (itemId) {
                this.originalItems.set(itemId, item.innerHTML);
            }
            
            // Проверяем, является ли элемент активным
            const isActive = item.querySelector('.mb-active') !== null;
            
            if (isActive && !this.initialActiveItem) {
                this.initialActiveItem = item;
            }
            
            // Добавляем плавную загрузку иконок
            setTimeout(() => {
                item.classList.add('mb-icon-loaded');
            }, 100 + index * 60); // Небольшая задержка для каждой следующей иконки
        });
    }

    // Показать навигацию
    showNavigation() {
        if (this.navigation) {
            requestAnimationFrame(() => {
                this.navigation.classList.add('mb-nav-loaded');
                this.navigation.classList.remove('mb-nav-hidden');
            });
        }
    }
    
    // Скрыть навигацию
    hideNavigation() {
        if (this.navigation) {
            requestAnimationFrame(() => {
                this.navigation.classList.add('mb-nav-hidden');
            });
        }
    }
    
    // Метод для преобразования иконки в кнопку "назад"
    convertIconToBackButton(iconId) {
        // Находим элемент по идентификатору
        const iconWrapper = this.iconsContainer.querySelector(`[data-icon-id="${iconId}"]`);
        
        if (!iconWrapper) {
            console.warn(`Иконка с идентификатором ${iconId} не найдена`);
            return false;
        }
        
        // Проверяем, не является ли иконка уже кнопкой "назад"
        if (iconWrapper.classList.contains('back-button-active')) {
            return true; // Уже преобразована
        }
        
        // Сохраняем оригинальное содержимое, если еще не сохранено
        if (!this.originalItems.has(iconId)) {
            this.originalItems.set(iconId, iconWrapper.innerHTML);
        }
        
        // Создаем кнопку "назад"
        iconWrapper.innerHTML = `
            <a class="mb-nav-link back-button" href="javascript:void(0);" title="Вернуться назад">
                <div class="mb-nav-icon-wrap">
                    <img class="mb-nav-icon" src="/images/icons/arrow-left.svg" alt="Назад"
                         onerror="this.src='/images/icons/back.svg';">
                </div>
            </a>
        `;
        
        // Добавляем класс для анимации
        iconWrapper.classList.add('back-button-active');
        
        // Добавляем обработчик для возврата
        const backButton = iconWrapper.querySelector('.back-button');
        backButton.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Закрываем открытое модальное окно
            if (window.modalPanel && window.modalPanel.activeModal) {
                window.modalPanel.closeModal();
            }
            
            // Восстанавливаем исходную иконку
            this.restoreIcon(iconId);
        });
        
        return true;
    }
    
    // Восстановление исходной иконки
    restoreIcon(iconId) {
        const iconWrapper = this.iconsContainer.querySelector(`[data-icon-id="${iconId}"]`);
        
        if (!iconWrapper) {
            console.warn(`Иконка с идентификатором ${iconId} не найдена для восстановления`);
            return false;
        }
        
        // Получаем оригинальное содержимое
        const originalContent = this.originalItems.get(iconId);
        
        if (!originalContent) {
            console.warn(`Не найдено оригинальное содержимое для иконки ${iconId}`);
            return false;
        }
        
        // Восстанавливаем оригинальное содержимое
        iconWrapper.innerHTML = originalContent;
        
        // Удаляем класс кнопки "назад"
        iconWrapper.classList.remove('back-button-active');
        
        return true;
    }
}

