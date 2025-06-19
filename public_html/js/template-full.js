/**
 * TemplateJS - Универсальный модульный JavaScript для шаблонов сертификатов
 * Версия 1.1
 */
(function() {
    // Создаем глобальный объект TemplateJS
    window.TemplateJS = {
        // Конфигурация по умолчанию
        config: {
            mode: null, // 'edit' или 'view', определяется автоматически
            selectors: {
                faqQuestion: '.faq-question',
                faqItem: '.faq-item',
                faqAnswer: '.faq-answer',
                dateStart: '.issue-date-s',
                dateEnd: '.issue-date-do',
                contactField: '[data-editable="faq_answer_1"]',
                editableElements: '[data-editable]',
                seriesQuantity: '[data-editable="series_quantity"]',
                seriesReceived: '[data-editable="series_received"]',
                scanCount: '[data-editable="scan_count"]',
                requiredScans: '[data-editable="required_scans"]'
            },
            debug: true // Режим отладки включен по умолчанию
        },

        /**
         * Инициализация всех компонентов шаблона
         * @param {Object} customConfig - Пользовательские настройки
         */
        init: function(customConfig = {}) {
            // Объединяем пользовательские настройки с настройками по умолчанию
            this.config = {...this.config, ...customConfig};
            
            // Определяем режим работы
            this.detectMode();
            
            // Инициализируем логгер
            this.logger = this.createLogger(this.config.debug);
            this.logger.info('Инициализация TemplateJS в режиме:', this.config.mode);
            
            // Инициализируем компоненты
            this.initComponents();
            
            // Экспортируем функции в глобальную область
            this.exportFunctions();
            
            return this;
        },
        
        /**
         * Определяет режим работы шаблона (просмотр или редактирование)
         */
        detectMode: function() {
            // Если режим не был явно задан, определяем его по URL
            if (!this.config.mode) {
                const url = window.location.href;
                if (url.includes('/template/')) {
                    this.config.mode = 'view';
                } else if (url.includes('/editor') || url.includes('/create-new')) {
                    this.config.mode = 'edit';
                } else {
                    // По умолчанию - режим редактирования
                    this.config.mode = 'edit';
                }
            }
            
            // Добавляем класс к body для стилей в зависимости от режима
            document.body.classList.add('template-' + this.config.mode);
            
            return this.config.mode;
        },
        
        /**
         * Создает объект для логгирования
         */
        createLogger: function(isDebug) {
            return {
                prefix: '[TemplateJS]',
                info: function(...args) {
                    if (isDebug) console.info(this.prefix, ...args);
                },
                warn: function(...args) {
                    if (isDebug) console.warn(this.prefix, ...args);
                },
                error: function(...args) {
                    console.error(this.prefix, ...args);
                }
            };
        },
        
        /**
         * Инициализирует все компоненты шаблона
         */
        initComponents: function() {
            try {
                // Инициализируем аккордеон для FAQ
                this.initAccordion();
                
                // Инициализируем выбор даты
                this.initDatePickers();
                
                // Обрабатываем ссылки в тексте
                this.processLinks();
                
                // Инициализируем заглушку QR-кода
                this.initQrCodePlaceholder();
                
                // Если режим просмотра - отключаем редактирование элементов
                if (this.config.mode === 'view') {
                    this.disableEditing();
                } else {
                    // В режиме редактирования инициализируем редактируемые поля
                    this.initEditableFields();
                }
                
                this.logger.info('Все компоненты инициализированы');
            } catch (error) {
                this.logger.error('Ошибка при инициализации компонентов:', error);
            }
        },
        
        /**
         * Инициализирует заглушку QR-кода в режиме редактирования
         */
        initQrCodePlaceholder: function() {
            const qrcodeContainer = document.getElementById('qrcode');
            if (!qrcodeContainer) return;
            
            // Если мы в режиме редактирования, показываем заглушку
            if (this.config.mode === 'edit') {
                const placeholder = qrcodeContainer.querySelector('.qrcode-placeholder');
                if (placeholder) {
                    placeholder.style.display = 'block';
                    this.logger.info('QR-код заглушка активирована');
                }
            } else {
                // В режиме просмотра скрываем заглушку
                const placeholder = qrcodeContainer.querySelector('.qrcode-placeholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            }
        },
        
        /**
         * Экспортирует основные функции в глобальную область видимости
         */
        exportFunctions: function() {
            // Используем стрелочные функции для сохранения контекста
            window.initFaqAccordion = () => this.initAccordion();
            window.initTemplateDatePickers = () => this.initDatePickers();
            window.processTemplateLinks = () => this.processLinks();
            window.linkify = (text) => this.linkify(text);
            window.getSeriesData = () => this.getSeriesData();
        },
        
        /**
         * Отключает редактирование элементов в режиме просмотра
         */
        disableEditing: function() {
            const editableElements = document.querySelectorAll(this.config.selectors.editableElements);
            editableElements.forEach(elem => {
                elem.removeAttribute('contenteditable');
                
                // Для input-элементов заменяем их на обычный текст
                if (elem.tagName === 'INPUT') {
                    const value = elem.value || elem.placeholder || '';
                    const span = document.createElement('span');
                    span.textContent = value;
                    if (elem.parentNode) {
                        elem.parentNode.replaceChild(span, elem);
                    }
                }
            });
            this.logger.info('Редактирование элементов отключено');
        },
        
        /**
         * Инициализирует редактируемые поля в режиме редактирования
         */
        initEditableFields: function() {
            const self = this;
            const editableElements = document.querySelectorAll(this.config.selectors.editableElements);
            
            editableElements.forEach(elem => {
                // Не делаем редактируемыми те элементы, которые уже имеют свои обработчики
                if (elem.classList.contains('issue-date-s') || 
                    elem.classList.contains('issue-date-do') ||
                    elem.getAttribute('data-editable') === 'faq_answer_1') {
                    return;
                }
                
                // Для input элементов уже есть базовая интерактивность
                if (elem.tagName !== 'INPUT') {
                    elem.setAttribute('contenteditable', 'true');
                }
                
                // Добавляем обработчик фокуса для визуального эффекта
                elem.addEventListener('focus', function() {
                    this.classList.add('editing');
                });
                
                // Убираем визуальный эффект при потере фокуса
                elem.addEventListener('blur', function() {
                    this.classList.remove('editing');
                });
                
                // Обработка нажатий клавиш
                elem.addEventListener('keydown', function(e) {
                    // Если нажат Enter в абзаце, предотвращаем перенос строки
                    if (e.key === 'Enter' && elem.tagName !== 'DIV' && !e.shiftKey) {
                        e.preventDefault();
                        elem.blur();
                    }
                });
            });
            
            this.logger.info('Редактируемые поля инициализированы');
        },

        /**
         * Инициализирует аккордеон для FAQ
         */
        initAccordion: function() {
            const self = this;
            const faqQuestions = document.querySelectorAll(this.config.selectors.faqQuestion);
            
            if (!faqQuestions.length) {
                this.logger.info('FAQ аккордеон: вопросы не найдены');
                return;
            }
            
            this.logger.info('Инициализация FAQ аккордеона:', faqQuestions.length, 'вопросов');
            
            faqQuestions.forEach((question, index) => {
                // Добавляем обработчик только если его еще нет
                if (!question.hasAttribute('data-accordion-initialized')) {
                    question.setAttribute('data-accordion-initialized', 'true');
                    
                    question.addEventListener('click', function() {
                        const faqItem = this.closest(self.config.selectors.faqItem);
                        if (!faqItem) return;
                        
                        const answer = faqItem.querySelector(self.config.selectors.faqAnswer);
                        if (!answer) return;
                        
                        const isActive = faqItem.classList.contains('active');
                        
                        // Закрываем все открытые вопросы
                        document.querySelectorAll(self.config.selectors.faqItem + '.active')
                            .forEach(item => {
                                if (item !== faqItem) {
                                    item.classList.remove('active');
                                    const itemAnswer = item.querySelector(self.config.selectors.faqAnswer);
                                    if (itemAnswer) itemAnswer.style.maxHeight = '0';
                                }
                            });
                        
                        // Переключаем состояние текущего вопроса
                        if (isActive) {
                            faqItem.classList.remove('active');
                            answer.style.maxHeight = '0';
                        } else {
                            faqItem.classList.add('active');
                            answer.style.maxHeight = answer.scrollHeight + 'px';
                        }
                        
                        self.logger.info('FAQ вопрос ' + (index + 1) + ' ' + (isActive ? 'закрыт' : 'открыт'));
                    });
                }
            });
        },
        
        /**
         * Инициализирует выбор даты
         */
        initDatePickers: function() {
            // Проверяем наличие flatpickr
            if (typeof flatpickr !== 'function') {
                this.logger.warn('Flatpickr не найден, загрузка flatpickr...');
                this.loadFlatpickr(() => this.initDatePickers());
                return;
            }
            
            const startDateEl = document.querySelector(this.config.selectors.dateStart);
            const endDateEl = document.querySelector(this.config.selectors.dateEnd);
            
            if (!startDateEl && !endDateEl) {
                this.logger.info('Поля для выбора даты не найдены');
                return;
            }
            
            // Инициализируем flatpickr для начальной даты
            if (startDateEl && !startDateEl.classList.contains('flatpickr-input')) {
                flatpickr(startDateEl, {
                    dateFormat: 'd F Y г.',
                    locale: 'ru',
                    allowInput: true,
                    disableMobile: false,
                    onOpen: function() {
                        startDateEl.classList.add('date-selecting');
                    },
                    onClose: function(selectedDates, dateStr) {
                        startDateEl.classList.remove('date-selecting');
                        if (dateStr) {
                            startDateEl.innerHTML = 'с: ' + dateStr;
                        }
                    }
                });
                this.logger.info('Выбор начальной даты инициализирован');
            }
            
            // Инициализируем flatpickr для конечной даты
            if (endDateEl && !endDateEl.classList.contains('flatpickr-input')) {
                flatpickr(endDateEl, {
                    dateFormat: 'd F Y г.',
                    locale: 'ru',
                    allowInput: true,
                    disableMobile: false,
                    onOpen: function() {
                        endDateEl.classList.add('date-selecting');
                    },
                    onClose: function(selectedDates, dateStr) {
                        endDateEl.classList.remove('date-selecting');
                        if (dateStr) {
                            endDateEl.innerHTML = 'до: ' + dateStr;
                        }
                    }
                });
                this.logger.info('Выбор конечной даты инициализирован');
            }
        },
        
        /**
         * Загружает библиотеку flatpickr
         */
        loadFlatpickr: function(callback) {
            let loadCount = 0;
            const totalFilesToLoad = 2;
            const onLoad = function() {
                loadCount++;
                if (loadCount >= totalFilesToLoad && callback) callback();
            };
            
            // Добавляем CSS если его нет
            if (!document.querySelector('link[href*="flatpickr"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
                link.onload = onLoad;
                document.head.appendChild(link);
            } else {
                loadCount++;
            }
            
            // Добавляем JS если его нет
            if (typeof flatpickr !== 'function') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
                script.onload = function() {
                    // Загружаем локализацию
                    const localeScript = document.createElement('script');
                    localeScript.src = 'https://npmcdn.com/flatpickr/dist/l10n/ru.js';
                    localeScript.onload = onLoad;
                    document.head.appendChild(localeScript);
                };
                document.head.appendChild(script);
            } else {
                loadCount++;
            }
        },
        
        /**
         * Обрабатывает ссылки в тексте
         */
        processLinks: function() {
            // Обрабатываем поле с контактами, если оно есть
            const contactField = document.querySelector(this.config.selectors.contactField);
            
            if (contactField && !contactField.getAttribute('data-links-processed')) {
                contactField.setAttribute('data-links-processed', 'true');
                const originalContent = contactField.innerHTML;
                contactField.innerHTML = this.linkify(originalContent);
                
                this.logger.info('Ссылки в контактах обработаны');
            }
        },
        
        /**
         * Преобразует текст со ссылками в HTML ссылки
         */
        linkify: function(text) {
            if (!text) return '';
            
            // Регулярное выражение для поиска URL
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            
            // Регулярное выражение для поиска email
            const emailRegex = /([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/g;
            
            // Заменяем URL на ссылки
            let result = text.replace(urlRegex, function(url) {
                return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
            });
            
            // Заменяем email на mailto ссылки
            result = result.replace(emailRegex, function(email) {
                return '<a href="mailto:' + email + '">' + email + '</a>';
            });
            
            return result;
        },
        
        /**
         * Получить данные о серии шаблона
         */
        getSeriesData: function() {
            return window.seriesDataFromServer || {
                is_series: false,
                series_quantity: 1,
                acquired_count: 0,
                scan_count: 0,
                required_scans: 1
            };
        }
    };

})();

/**
 * Инициализируем модуль TemplateJS после загрузки DOM
 */
document.addEventListener('DOMContentLoaded', function() {
    // Задержка перед инициализацией, чтобы дать время загрузиться всем компонентам
    setTimeout(function() {
        // Инициализируем TemplateJS
        if (window.TemplateJS) {
            window.TemplateJS.init({
                debug: true,
                mode: window.location.href.includes('/editor') || window.location.href.includes('/create-new') ? 
                    'edit' : 'view'
            });
        }
    }, 300);
});

// Создаем глобальный объект TemplateCore с теми же методами для обратной совместимости
window.TemplateCore = {
    initFaqAccordion: function() {
        if (window.TemplateJS) window.TemplateJS.initAccordion();
    },
    initDatePickers: function() {
        if (window.TemplateJS) window.TemplateJS.initDatePickers();
    },
    processLinks: function() {
        if (window.TemplateJS) window.TemplateJS.processLinks();
    }
};

// Определяем полифилл для метода closest, если его нет в браузере
if (!Element.prototype.closest) {
    Element.prototype.closest = function(s) {
        let el = this;
        do {
            if (Element.prototype.matches.call(el, s)) return el;
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}
