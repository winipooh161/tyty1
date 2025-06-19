/**
 * Модуль обработки публичного шаблона
 */
const PublicTemplateHandler = (function() {
    let initialized = false;
    
    /**
     * Инициализация обработчика публичного шаблона
     */
    function init() {
        if (initialized) return;
        
        // Инициализируем аккордеон FAQ
        initFaqAccordion();
        
        // Инициализируем обработку дат
        initDatePickers();
        
        initialized = true;
        console.log('PublicTemplateHandler initialized');
    }
    
    /**
     * Инициализация аккордеона FAQ
     */
    function initFaqAccordion() {
        const faqQuestions = document.querySelectorAll('.faq-question');
        console.log(`Found ${faqQuestions.length} FAQ questions`);
        
        faqQuestions.forEach(function(question) {
            question.addEventListener('click', function() {
                // Получаем родительский элемент
                const faqItem = this.closest('.faq-item');
                
                // Получаем ответ (следующий элемент после faq-question)
                const answer = this.nextElementSibling;
                
                if (!answer || !faqItem) {
                    console.error('FAQ answer element or item not found');
                    return;
                }
                
                // Переключаем класс active
                if (faqItem.classList.contains('active')) {
                    faqItem.classList.remove('active');
                    answer.style.maxHeight = '0';
                } else {
                    // Закрываем все другие вопросы
                    document.querySelectorAll('.faq-item.active').forEach(item => {
                        item.classList.remove('active');
                        const itemAnswer = item.querySelector('.faq-answer');
                        if (itemAnswer) itemAnswer.style.maxHeight = '0';
                    });
                    
                    // Открываем текущий вопрос
                    faqItem.classList.add('active');
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                }
                
                console.log('FAQ question clicked, item active:', faqItem.classList.contains('active'));
            });
        });
        
        console.log('FAQ accordion initialized');
    }
    
    /**
     * Инициализация выбора даты
     */
    function initDatePickers() {
        // Проверяем, загружена ли flatpickr
        if (typeof flatpickr === 'function') {
            console.log('Flatpickr found, initializing date pickers...');
            
            // Ищем все элементы для инициализации
            const startDateElem = document.querySelector('.issue-date-s');
            const endDateElem = document.querySelector('.issue-date-do');
            
            // Инициализация для стартовой даты
            if (startDateElem) {
                flatpickr(startDateElem, {
                    dateFormat: 'd F Y г.',
                    locale: 'ru',
                    allowInput: true,
                    disableMobile: false,
                    onOpen: function(selectedDates, dateStr, instance) {
                        startDateElem.classList.add('date-selecting');
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        startDateElem.classList.remove('date-selecting');
                        if (dateStr) {
                            startDateElem.innerHTML = 'с: ' + dateStr;
                        }
                    }
                });
                
                console.log('Start date picker initialized');
            }
            
            // Инициализация для конечной даты
            if (endDateElem) {
                flatpickr(endDateElem, {
                    dateFormat: 'd F Y г.',
                    locale: 'ru',
                    allowInput: true,
                    disableMobile: false,
                    onOpen: function(selectedDates, dateStr, instance) {
                        endDateElem.classList.add('date-selecting');
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        endDateElem.classList.remove('date-selecting');
                        if (dateStr) {
                            endDateElem.innerHTML = 'до: ' + dateStr;
                        }
                    }
                });
                
                console.log('End date picker initialized');
            }
        } else {
            console.warn('Flatpickr not found, trying to load it...');
            
            // Загружаем flatpickr если его нет
            loadFlatpickr(function() {
                initDatePickers(); // Рекурсивный вызов после загрузки
            });
        }
    }
    
    /**
     * Загрузка flatpickr если его нет
     */
    function loadFlatpickr(callback) {
        // Проверяем наличие CSS
        if (!document.querySelector('link[href*="flatpickr"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
            document.head.appendChild(link);
        }
        
        // Проверяем наличие JS
        if (typeof flatpickr !== 'function') {
            // Основной скрипт
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
            script.onload = function() {
                // После загрузки основного скрипта загружаем русскую локализацию
                const localeScript = document.createElement('script');
                localeScript.src = 'https://npmcdn.com/flatpickr/dist/l10n/ru.js';
                localeScript.onload = function() {
                    if (callback && typeof callback === 'function') {
                        setTimeout(callback, 100);
                    }
                };
                document.head.appendChild(localeScript);
            };
            document.head.appendChild(script);
        } else if (callback && typeof callback === 'function') {
            callback();
        }
    }
    
    return {
        init: init,
        initFaqAccordion: initFaqAccordion,
        initDatePickers: initDatePickers
    };
})();

/**
 * Series Template Handler - модуль для обработки серийных шаблонов
 * Взаимодействует с TemplateJS из шаблонов
 */
const SeriesTemplateHandler = (function() {
    // Приватные переменные
    let config = {};
    let seriesData = {};
    
    /**
     * Инициализация обработчика серийных шаблонов
     * @param {Object} options - Параметры инициализации
     */
    function init(options = {}) {
        console.log('SeriesTemplateHandler: Инициализация');
        
        // Сохраняем конфигурацию
        config = {
            selectors: {
                seriesQuantity: '[data-editable="series_quantity"]',
                seriesReceived: '[data-editable="series_received"]',
                scanCount: '[data-editable="scan_count"]',
                requiredScans: '[data-editable="required_scans"]'
            },
            ...options
        };
        
        // Извлекаем и выполняем скрипты из шаблона
        extractAndRunScripts();
        
        // Попытка инициализации TemplateJS вручную, если он доступен
        initializeTemplateJS();
        
        // Обновляем пользовательский интерфейс
        updateUI();
        
        return this;
    }
    
    /**
     * Извлекает и выполняет все скрипты из шаблона
     */
    function extractAndRunScripts() {
        try {
            const templateContent = document.getElementById('template-content');
            if (!templateContent) return;
            
            console.log('Extracting scripts from template content');
            
            // Находим все скрипты в контенте
            const scriptElements = templateContent.querySelectorAll('script');
            if (scriptElements.length === 0) {
                console.log('No script elements found in template');
                return;
            }
            
            console.log(`Found ${scriptElements.length} script elements`);
            
            // Обрабатываем каждый скрипт
            scriptElements.forEach((oldScript, index) => {
                // Создаем новый элемент script
                const newScript = document.createElement('script');
                
                // Копируем атрибуты
                Array.from(oldScript.attributes).forEach(attr => {
                    newScript.setAttribute(attr.name, attr.value);
                });
                
                // Получаем содержимое скрипта
                let scriptContent = oldScript.innerHTML;
                
                // Проверяем, не обрезан ли скрипт
                if (scriptContent.includes('addEven…') || scriptContent.endsWith('addEven')) {
                    console.warn('Script content was truncated, using full script content');
                    
                    // Исправляем обрезанное содержимое для elem.addEventListener
                    scriptContent = scriptContent.replace('elem.addEven…', 
                        'elem.addEventListener(\'keydown\', function(e) { if (e.key === \'Enter\') { e.preventDefault(); this.blur(); } });');
                }
                
                // Устанавливаем содержимое скрипта
                newScript.text = scriptContent;
                
                // Удаляем старый скрипт
                if (oldScript.parentNode) {
                    oldScript.parentNode.removeChild(oldScript);
                }
                
                // Добавляем новый скрипт в документ
                document.body.appendChild(newScript);
                
                console.log(`Script ${index + 1} executed`);
            });
            
            console.log('All scripts from template executed');
        } catch (error) {
            console.error('Error extracting and running scripts:', error);
        }
    }
    
    /**
     * Попытка инициализировать TemplateJS вручную
     */
    function initializeTemplateJS() {
        console.log('SeriesTemplateHandler: Проверка наличия TemplateJS');
        
        if (window.TemplateJS) {
            console.log('SeriesTemplateHandler: TemplateJS найден, инициализация');
            
            // Проверяем, был ли уже инициализирован TemplateJS
            if (!window.TemplateJS.initialized) {
                try {
                    window.TemplateJS.init({
                        debug: true,
                        mode: isEditMode() ? 'edit' : 'view'
                    });
                    window.TemplateJS.initialized = true;
                    console.log('SeriesTemplateHandler: TemplateJS инициализирован');
                } catch (error) {
                    console.error('SeriesTemplateHandler: Ошибка инициализации TemplateJS', error);
                }
            } else {
                console.log('SeriesTemplateHandler: TemplateJS уже инициализирован');
            }
            
            // Принудительно вызываем некоторые функции из TemplateJS
            try {
                if (typeof window.initFaqAccordion === 'function') {
                    window.initFaqAccordion();
                    console.log('SeriesTemplateHandler: FAQ аккордеон инициализирован');
                }
                
                if (typeof window.initTemplateDatePickers === 'function') {
                    window.initTemplateDatePickers();
                    console.log('SeriesTemplateHandler: Выбор дат инициализирован');
                }
                
                if (typeof window.processTemplateLinks === 'function') {
                    window.processTemplateLinks();
                    console.log('SeriesTemplateHandler: Ссылки обработаны');
                }
            } catch (error) {
                console.warn('SeriesTemplateHandler: Ошибка вызова вспомогательных функций', error);
            }
        } else {
            console.warn('SeriesTemplateHandler: TemplateJS не найден');
            
            // Если TemplateJS не найден, пробуем инициализировать аккордеон сами
            initFaqAccordion();
        }
    }
    
    /**
     * Определяет, находимся ли мы в режиме редактирования
     * @returns {boolean} true если это режим редактирования
     */
    function isEditMode() {
        const url = window.location.href;
        return url.includes('/editor') || url.includes('/create-new');
    }
    
    /**
     * Резервная функция инициализации аккордеона FAQ
     */
    function initFaqAccordion() {
        console.log('SeriesTemplateHandler: Резервная инициализация FAQ аккордеона');
        
        const faqQuestions = document.querySelectorAll('.faq-question');
        console.log(`SeriesTemplateHandler: Найдено ${faqQuestions.length} вопросов FAQ`);
        
        faqQuestions.forEach(function(question) {
            // Проверяем, не был ли уже добавлен обработчик
            if (!question.hasAttribute('data-handler-attached')) {
                question.setAttribute('data-handler-attached', 'true');
                
                question.addEventListener('click', function() {
                    const faqItem = this.closest('.faq-item');
                    
                    if (faqItem) {
                        const isActive = faqItem.classList.contains('active');
                        
                        // Закрываем все элементы
                        document.querySelectorAll('.faq-item').forEach(item => {
                            item.classList.remove('active');
                        });
                        
                        // Если элемент не был активен, открываем его
                        if (!isActive) {
                            faqItem.classList.add('active');
                        }
                    }
                });
                
                console.log('SeriesTemplateHandler: Добавлен обработчик для FAQ вопроса');
            }
        });
        
        // Инициализируем flatpickr если он есть
        if (typeof flatpickr === 'function') {
            const dateElements = document.querySelectorAll('.issue-date-s, .issue-date-do');
            
            dateElements.forEach(elem => {
                flatpickr(elem, {
                    dateFormat: "j F Y г.",
                    locale: "ru",
                    allowInput: true,
                    onOpen: function() {
                        elem.classList.add('date-selecting');
                    },
                    onClose: function() {
                        elem.classList.remove('date-selecting');
                    }
                });
            });
            
            console.log('SeriesTemplateHandler: Инициализирован flatpickr');
        }
    }
    
    /**
     * Обновляет пользовательский интерфейс серийного шаблона
     */
    function updateUI() {
        try {
            // Проверяем существование полей серии
            const seriesQuantityField = document.querySelector(config.selectors.seriesQuantity);
            const seriesReceivedField = document.querySelector(config.selectors.seriesReceived);
            const scanCountField = document.querySelector(config.selectors.scanCount);
            const requiredScansField = document.querySelector(config.selectors.requiredScans);
            
            if (seriesQuantityField || seriesReceivedField) {
                console.log('SeriesTemplateHandler: Обнаружены поля серийного шаблона');
                
                // Добавляем слушатели событий для полей ввода серии
                if (seriesQuantityField) {
                    seriesQuantityField.addEventListener('change', updateSeriesData);
                    console.log('SeriesTemplateHandler: Добавлен обработчик для поля количества элементов серии');
                }
                
                if (requiredScansField) {
                    requiredScansField.addEventListener('change', updateSeriesData);
                    console.log('SeriesTemplateHandler: Добавлен обработчик для поля требуемых сканирований');
                }
                
                // Инициализируем данные серии
                updateSeriesData();
            }
        } catch (error) {
            console.error('SeriesTemplateHandler: Ошибка обновления UI', error);
        }
    }
    
    /**
     * Обновляет данные серии на основе полей ввода
     */
    function updateSeriesData() {
        try {
            // Получаем значения полей
            const seriesQuantityField = document.querySelector(config.selectors.seriesQuantity);
            const requiredScansField = document.querySelector(config.selectors.requiredScans);
            
            let quantityValue = 1;
            let requiredScans = 1;
            
            // Получаем значения в зависимости от типа элемента
            if (seriesQuantityField) {
                if (seriesQuantityField.tagName === 'INPUT') {
                    quantityValue = parseInt(seriesQuantityField.value || '1', 10);
                } else {
                    quantityValue = parseInt(seriesQuantityField.textContent.trim() || '1', 10);
                }
            }
            
            if (requiredScansField) {
                if (requiredScansField.tagName === 'INPUT') {
                    requiredScans = parseInt(requiredScansField.value || '1', 10);
                } else {
                    requiredScans = parseInt(requiredScansField.textContent.trim() || '1', 10);
                }
            }
            
            // Проверяем валидность значений
            if (isNaN(quantityValue) || quantityValue < 1) quantityValue = 1;
            if (isNaN(requiredScans) || requiredScans < 1) requiredScans = 1;
            
            // Сохраняем данные серии
            seriesData = {
                is_series: quantityValue > 1,
                series_quantity: quantityValue,
                required_scans: requiredScans
            };
            
            // Обновляем скрытые поля формы, если они существуют
            const isSeriesTemplate = document.getElementById('is_series_template');
            const seriesQuantityValue = document.getElementById('series_quantity_value');
            const requiredScansValue = document.getElementById('required_scans_value');
            
            if (isSeriesTemplate) isSeriesTemplate.value = seriesData.is_series ? '1' : '0';
            if (seriesQuantityValue) seriesQuantityValue.value = seriesData.series_quantity.toString();
            if (requiredScansValue) requiredScansValue.value = seriesData.required_scans.toString();
            
            console.log('SeriesTemplateHandler: Данные серии обновлены', seriesData);
        } catch (error) {
            console.error('SeriesTemplateHandler: Ошибка обновления данных серии', error);
        }
    }
    
    /**
     * Получить текущие данные серии
     * @returns {Object} данные о серии
     */
    function getSeriesData() {
        return seriesData;
    }
    
    // Публичные методы
    return {
        init,
        getSeriesData,
        updateSeriesData,
        extractAndRunScripts
    };
})();

// Экспортируем SeriesTemplateHandler в глобальную область видимости
window.SeriesTemplateHandler = SeriesTemplateHandler;

/**
 * Запускаем обработчики после загрузки DOM
 */
document.addEventListener('DOMContentLoaded', function() {
    // Запускаем обработчик публичного шаблона
    PublicTemplateHandler.init();
    
    // Экспортируем функцию для глобального доступа
    window.initFaqAccordion = function() {
        PublicTemplateHandler.initFaqAccordion();
    };
    
    // Функция инициализации flatpickr
    window.initFlatpickr = function() {
        PublicTemplateHandler.initDatePickers();
    };
    
    console.log('DOM загружен, проверяем необходимость инициализации SeriesTemplateHandler');
    
    // Проверяем наличие полей серии в DOM
    const hasSeriesFields = document.querySelector('[data-editable="series_quantity"]') !== null;
    const templateContent = document.getElementById('template-content');
    
    if (hasSeriesFields || templateContent) {
        console.log('Найден контент шаблона, инициализируем SeriesTemplateHandler');
        SeriesTemplateHandler.init();
    } else {
        console.log('Контент шаблона не найден, откладываем инициализацию SeriesTemplateHandler');
        
        // Добавляем отложенную инициализацию для случаев, когда DOM может измениться
        const observer = new MutationObserver(function(mutations) {
            if (document.querySelector('[data-editable="series_quantity"]') !== null || 
                document.getElementById('template-content')) {
                console.log('Контент шаблона обнаружен после изменения DOM, инициализируем SeriesTemplateHandler');
                SeriesTemplateHandler.init();
                observer.disconnect();
            }
        });
        
        // Наблюдаем за изменениями в содержимом body
        observer.observe(document.body, { 
            childList: true, 
            subtree: true 
        });
        
        // Останавливаем наблюдение через 10 секунд
        setTimeout(function() {
            observer.disconnect();
        }, 10000);
    }
});

/**
 * Функция для восстановления полных скриптов шаблона из внешних источников
 * при необходимости
 */
function loadFullTemplateScripts() {
    // Проверяем наличие обрезанных скриптов
    const templateContent = document.getElementById('template-content');
    if (!templateContent) return;
    
    const scriptElements = templateContent.querySelectorAll('script');
    const hasTruncatedScripts = Array.from(scriptElements).some(script => 
        script.innerHTML.includes('addEven…') || 
        script.innerHTML.endsWith('addEven')
    );
    
    if (hasTruncatedScripts) {
        console.log('Обнаружены обрезанные скрипты, загружаем полную версию');
        
        // Загружаем полную версию TemplateJS
        const templateJsScript = document.createElement('script');
        templateJsScript.src = '/js/template-full.js?v=' + new Date().getTime();
        templateJsScript.onload = function() {
            console.log('Полная версия скрипта загружена');
            
            // Инициализируем TemplateJS с небольшой задержкой
            setTimeout(function() {
                if (window.TemplateJS && typeof window.TemplateJS.init === 'function') {
                    window.TemplateJS.init({
                        debug: true,
                        mode: window.location.href.includes('/editor') || window.location.href.includes('/create-new') ? 
                            'edit' : 'view'
                    });
                    console.log('TemplateJS инициализирован из полной версии скрипта');
                }
            }, 100);
        };
        document.body.appendChild(templateJsScript);
    }
}

// Выполняем проверку и загрузку полных скриптов после небольшой задержки
setTimeout(loadFullTemplateScripts, 1000);
