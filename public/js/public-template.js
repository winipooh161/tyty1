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
 * SeriesTemplateHandler - модуль для обработки серийных шаблонов
 * и их отображения на публичной странице
 */
window.SeriesTemplateHandler = (function() {
    let config = {
        debug: true,
        selectors: {
            seriesQuantity: '[data-editable="series_quantity"]',
            seriesReceived: '[data-editable="series_received"]',
            scanCount: '[data-editable="scan_count"]',
            requiredScans: '[data-editable="required_scans"]'
        },
        seriesData: null
    };
    
    /**
     * Инициализация обработчика
     */
    function init(options = {}) {
        // Объединяем настройки
        config = {...config, ...options};
        
        // Получаем данные о серии
        config.seriesData = window.seriesDataFromServer || null;
        
        if (config.debug) {
            console.log('SeriesTemplateHandler initialized with data:', config.seriesData);
        }
        
        // Если есть данные о серии, заполняем поля
        if (config.seriesData) {
            setTimeout(fillSeriesData, 300);
        }
        
        // Добавляем обработчики форм получения шаблона
        initAcquireHandlers();
        
        return this;
    }
    
    /**
     * Инициализация обработчиков получения шаблона
     */
    function initAcquireHandlers() {
        // Находим форму получения шаблона
        const acquireForm = document.getElementById('acquireTemplateForm');
        
        if (acquireForm) {
            if (config.debug) {
                console.log('Found acquire form, setting up handler');
            }
            
            // Проверяем наличие всех необходимых данных для отправки
            const token = acquireForm.querySelector('input[name="_token"]');
            const submitBtn = acquireForm.querySelector('button[type="submit"]');
            
            if (!token) {
                console.error('CSRF token not found in form!');
                
                // Создаем и добавляем токен, если его нет
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = csrfToken.getAttribute('content');
                    acquireForm.appendChild(tokenInput);
                    
                    console.log('Added CSRF token to form');
                }
            }
        }
    }
    
    /**
     * Заполнение полей данными о серии
     */
    function fillSeriesData() {
        try {
            if (!config.seriesData) {
                if (config.debug) {
                    console.warn('No series data available');
                }
                return;
            }
            
            // Обновляем поле количества
            updateField(
                config.selectors.seriesQuantity, 
                config.seriesData.series_quantity
            );
            
            // Обновляем поле полученных шаблонов
            updateField(
                config.selectors.seriesReceived, 
                config.seriesData.acquired_count
            );
            
            // Обновляем количество сканирований
            updateField(
                config.selectors.scanCount, 
                config.seriesData.scan_count
            );
            
            // Обновляем требуемое количество сканирований
            updateField(
                config.selectors.requiredScans, 
                config.seriesData.required_scans
            );
            
            // Проверяем, можно ли получить шаблон (остались ли свободные экземпляры)
            const availableCount = config.seriesData.series_quantity - config.seriesData.acquired_count;
            if (availableCount <= 0) {
                const acquireForm = document.getElementById('acquireTemplateForm');
                if (acquireForm) {
                    const submitBtn = acquireForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i> Нет доступных экземпляров';
                    }
                    
                    // Добавляем предупреждение
                    const warningEl = document.createElement('div');
                    warningEl.className = 'alert alert-warning mt-2';
                    warningEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Все доступные экземпляры уже разобраны';
                    acquireForm.appendChild(warningEl);
                }
            }
            
            if (config.debug) {
                console.log('Series data filled successfully:', config.seriesData);
            }
        } catch (error) {
            console.error('Error while filling series data:', error);
        }
    }
    
    /**
     * Обновление значения поля
     */
    function updateField(selector, value) {
        try {
            const field = document.querySelector(selector);
            if (!field) return;
            
            if (config.debug) {
                console.log(`Updating field ${selector.replace('[data-editable="', '').replace('"]', '')} with value ${value}`, field);
            }
            
            if (field.tagName === 'INPUT') {
                field.value = value;
                // Если поле имеет placeholder, обновляем его тоже
                if (field.hasAttribute('placeholder')) {
                    field.placeholder = value;
                }
            } else {
                field.textContent = value;
            }
        } catch (error) {
            console.error(`Error updating field ${selector}:`, error);
        }
    }
    
    /**
     * Извлечение и выполнени
    /**
     * Инициализация обработчиков событий
     */
    function initEventHandlers() {
        // Обрабатываем нажатия на кнопки
        document.querySelectorAll('form[onsubmit*="handleFormSubmit"]').forEach(form => {
            form.addEventListener('submit', function(event) {
                // Форма будет отправлена через стандартный механизм
                // Но мы можем добавить дополнительные проверки
                const submitButton = form.querySelector('button[type="submit"]');
                
                if (submitButton) {
                    // Отключаем кнопку для предотвращения повторной отправки
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Получение...';
                }
            });
        });
        
        // Обрабатываем закрытие уведомлений
        document.querySelectorAll('.alert .btn-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                const alert = this.closest('.alert');
                if (alert) {
                    alert.style.display = 'none';
                }
            });
        });
    }
    
    // Публичный API
    return {
        init
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
    // Проверяем наличие обрезанных скриптов в шаблоне
    const templateContent = document.getElementById('template-content') || document.getElementById('template-html-content');
    if (!templateContent) {
        console.log('Контент шаблона не найден');
        return;
    }
    
    const scriptElements = templateContent.querySelectorAll('script');
    
    // Расширяем список паттернов обрезания
    const hasTruncatedScripts = Array.from(scriptElements).some(script => {
        const scriptContent = script.innerHTML || '';
        
        // Проверяем различные признаки обрезанных скриптов
        return scriptContent.includes('addEven…') || 
               scriptContent.endsWith('addEven') || 
               scriptContent.includes('…') || 
               /addevent[a-z]*$/i.test(scriptContent) ||
               scriptContent.includes('function() {') && !scriptContent.includes('function() { }') ||
               (scriptContent.includes('TemplateJS') && scriptContent.length < 1000);
    });
    
    console.log(`Проверка наличия обрезанных скриптов: ${hasTruncatedScripts ? 'найдены' : 'не найдены'}`);
    
    if (hasTruncatedScripts) {
        console.log('Обнаружены обрезанные скрипты, загружаем полную версию');
        
        // Загружаем полную версию TemplateJS
        const templateJsScript = document.createElement('script');
        templateJsScript.src = '/js/template-full.js?v=' + new Date().getTime();
        
        // Добавляем обработчик событий для отслеживания загрузки скрипта
        templateJsScript.addEventListener('load', function() {
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
                
                // Также вызываем глобальные функции инициализации, если они определены
                if (typeof window.initFaqAccordion === 'function') {
                    window.initFaqAccordion();
                }
                if (typeof window.initTemplateDatePickers === 'function') {
                    window.initTemplateDatePickers();
                }
                if (typeof window.processTemplateLinks === 'function') {
                    window.processTemplateLinks();
                }
            }, 200);
        });
        
        templateJsScript.addEventListener('error', function() {
            console.error('Ошибка загрузки полной версии скрипта');
            
            // В качестве запасного варианта загружаем скрипт с CDN
            const backupScript = document.createElement('script');
            backupScript.src = 'https://cdn.jsdelivr.net/gh/tytyproject/templates@main/template-full.js?v=' + new Date().getTime();
            document.body.appendChild(backupScript);
        });
        
        document.body.appendChild(templateJsScript);
    }
}

// Выполняем проверку и загрузку полных скриптов с задержкой
setTimeout(loadFullTemplateScripts, 500);

// Добавляем дополнительную проверку после полной загрузки документа
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(loadFullTemplateScripts, 1000);
    
    // Повторная проверка через 3 секунды для случаев асинхронной загрузки контента
    setTimeout(loadFullTemplateScripts, 3000);
});
