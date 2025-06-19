/**
 * Модуль редактора шаблонов
 */
const TemplateEditor = (function() {
    // Приватные переменные
    let config = {};
    let customData = {};
    
    /**
     * Инициализация редактора шаблонов
     * @param {Object} options - Параметры инициализации
     */
    function init(options) {
        // Сохраняем конфигурацию
        config = options || {};
        
        // Инициализируем обработку кнопок
        initButtons();
        
        // Загружаем необходимые внешние библиотеки
        loadRequiredLibraries(['flatpickr'], function() {
            // После загрузки библиотек загружаем контент шаблона
            loadTemplateContent();
        });
        
        // Инициализируем обработку обложки
        initCoverHandling();
        
        // Инициализируем отладку мобильной навигации
        initMobileNavDebug();
        
        // Парсим пользовательские данные
        try {
            const customDataInput = document.getElementById('custom_data');
            if (customDataInput && customDataInput.value) {
                customData = JSON.parse(customDataInput.value);
            }
        } catch (e) {
            console.error('Ошибка при парсинге пользовательских данных', e);
        }
        
        // Экспортируем функцию сохранения в глобальную область видимости
        window.saveTemplateForm = saveTemplateForm;
        
        // Исправление проблем с прокруткой на мобильных устройствах
        fixMobileScrolling();
    }
    
    /**
     * Инициализация обработчиков кнопок
     */
    function initButtons() {
        const saveButton = document.getElementById('save-template-btn');
        if (saveButton) {
            saveButton.addEventListener('click', function(e) {
                e.preventDefault();
                saveTemplateForm();
            });
        }
    }
    
    /**
     * Инициализация обработки обложки
     */
    function initCoverHandling() {
        const coverContainer = document.getElementById('coverPreviewContainer');
        const returnToCover = document.getElementById('returnToCover');
        const toggleCoverBtn = document.getElementById('toggleCoverBtn');
        
        if (!coverContainer || !toggleCoverBtn) return;
        
        let isCoverHidden = false;
        const skipBtnText = document.getElementById('skipBtnText');
        
        // Функция для переключения видимости обложки
        function toggleCover() {
            if (isCoverHidden) {
                // Показываем обложку
                coverContainer.classList.remove('cover-hidden');
                toggleCoverBtn.classList.remove('btn-success');
                toggleCoverBtn.classList.add('btn-outline-secondary');
               
                isCoverHidden = false;
            } else {
                // Скрываем обложку
                coverContainer.classList.add('cover-hidden');
                toggleCoverBtn.classList.remove('btn-outline-secondary');
                toggleCoverBtn.classList.add('btn-success');
              
                isCoverHidden = true;
            }
        }
        
        // Обработчик клика по кнопке
        toggleCoverBtn.addEventListener('click', toggleCover);
        
        // Обработчик клика по индикатору возврата
        if (returnToCover) {
            returnToCover.addEventListener('click', function() {
                if (isCoverHidden) {
                    toggleCover();
                }
            });
        }
        
        // Автоматическое воспроизведение видео при загрузке
        const coverVideo = document.getElementById('coverVideo');
        if (coverVideo) {
            coverVideo.addEventListener('canplaythrough', function() {
                coverVideo.play().catch(function(error) {
                    console.log('Автоматическое воспроизведение видео не удалось:', error);
                });
            });
        }
    }
    
    /**
     * Инициализация отладки мобильной навигации
     */
    function initMobileNavDebug() {
        setTimeout(function() {
            const mbNavigation = document.querySelector('.mb-navigation');
            
            if (mbNavigation) {
                console.log('Mobile navigation exists:', mbNavigation);
                
                // Принудительно показываем навигацию
                mbNavigation.style.display = 'flex';
                
                // Обновляем отладочную информацию
                if (document.getElementById('debug-current-url')) {
                    document.getElementById('debug-current-url').textContent = window.location.href;
                    document.getElementById('debug-is-editor').textContent = 
                        window.location.href.includes('templates/editor') ? 'Да' : 'Нет';
                    document.getElementById('debug-nav-visible').textContent = 
                        (mbNavigation.style.display !== 'none') ? 'Да' : 'Нет';
                }
            }
        }, 500);
    }
    
    /**
     * Загрузка необходимых внешних библиотек
     * @param {Array} libraries - Список библиотек для загрузки
     * @param {Function} callback - Функция, вызываемая после загрузки всех библиотек
     */
    function loadRequiredLibraries(libraries, callback) {
        // Счетчик загруженных библиотек
        let loadedCount = 0;
        const requiredCount = libraries.length;
        
        // Если библиотек для загрузки нет, сразу вызываем callback
        if (requiredCount === 0) {
            callback();
            return;
        }
        
        // Проверяем, загружены ли уже библиотеки
        const toLoad = libraries.filter(lib => !window[lib]);
        
        // Если все библиотеки загружены, вызываем callback
        if (toLoad.length === 0) {
            callback();
            return;
        }
        
        // Устанавливаем обработчик для проверки загрузки всех библиотек
        function checkAllLoaded() {
            loadedCount++;
            if (loadedCount === toLoad.length) {
                callback();
            }
        }
        
        // Загружаем необходимые библиотеки
        toLoad.forEach(lib => {
            switch (lib) {
                case 'flatpickr':
                    loadFlatpickr(checkAllLoaded);
                    break;
                default:
                    checkAllLoaded();
            }
        });
    }
    
    /**
     * Загрузка библиотеки flatpickr
     * @param {Function} callback - Функция, вызываемая после загрузки
     */
    function loadFlatpickr(callback) {
        // Загружаем стили
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
        document.head.appendChild(link);
        
        // Загружаем скрипт
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
        script.onload = callback;
        document.head.appendChild(script);
    }
    
    /**
     * Загрузка HTML-контента шаблона
     */
    function loadTemplateContent() {
        const templatePreview = document.getElementById('template-preview');
        const templateLoading = document.getElementById('template-loading');
        const templateContent = document.getElementById('template-content');
        
        if (!templatePreview || !templateLoading || !templateContent) return;
        
        // Показываем индикатор загрузки
        templateLoading.style.display = 'flex';
        templateContent.style.display = 'none';
        
        // Получаем HTML-контент
        let htmlContent = '';
        if (config.userTemplate && config.userTemplate.html_content) {
            htmlContent = config.userTemplate.html_content;
        } else if (config.template && config.template.html_content) {
            htmlContent = config.template.html_content;
        }
        
        // Устанавливаем HTML-контент с небольшой задержкой
        setTimeout(() => {
            templateContent.innerHTML = htmlContent;
            templateLoading.style.display = 'none';
            templateContent.style.display = 'block';
            
            // Инициализируем редактируемые элементы
            initializeEditableElements();
            
            // Выполняем скрипты в загруженном контенте
            executeScripts();
            
            // Сразу заполняем скрытое поле с HTML контентом
            document.getElementById('html_content').value = htmlContent;
            
            console.log('Template content loaded successfully');
        }, 100);
    }
    
    /**
     * Инициализация редактируемых элементов
     */
    function initializeEditableElements() {
        const templateContent = document.getElementById('template-content');
        if (!templateContent) return;
        
        const editableElements = templateContent.querySelectorAll('[data-editable]');
        
        editableElements.forEach(element => {
            // Обработка клика для входа в режим редактирования
            element.addEventListener('click', function(e) {
                e.stopPropagation(); // Предотвращаем всплытие события
                focusAndEnableEditing(this);
            });
            
            // Добавляем обработчики событий в зависимости от типа элемента
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.tagName === 'SELECT') {
                // Для элементов формы добавляем обработчик blur
                element.addEventListener('blur', function() {
                    this.classList.remove('editing');
                });
            } else {
                // Для элементов, которые не являются формами, добавляем contenteditable
                element.setAttribute('contenteditable', 'true');
                
                // Добавляем обработчик blur
                element.addEventListener('blur', function() {
                    this.classList.remove('editing');
                });
                
                // Предотвращаем добавление div при нажатии Enter
                element.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur(); // Выход из режима редактирования
                    }
                });
            }
        });
    }
    
    /**
     * Фокусировка и активация режима редактирования
     * @param {HTMLElement} element - Элемент для фокусировки
     */
    function focusAndEnableEditing(element) {
        // Снимаем выделение с других элементов
        document.querySelectorAll('[data-editable].editing').forEach(el => {
            el.classList.remove('editing');
        });
        
        // Переключаем режим редактирования
        element.classList.add('editing');
        
        if (element.tagName === 'INPUT') {
            element.select();
        } else {
            // Для contenteditable элементов ставим курсор в конец
            const range = document.createRange();
            const sel = window.getSelection();
            range.selectNodeContents(element);
            range.collapse(false); // collapse to end
            sel.removeAllRanges();
            sel.addRange(range);
            element.focus();
        }
    }
    
    /**
     * Выполнение скриптов внутри загруженного контента
     */
    function executeScripts() {
        const templateContent = document.getElementById('template-content');
        if (!templateContent) return;
        
        // Найти все скрипты в загруженном контенте
        const scripts = templateContent.querySelectorAll('script');
        
        scripts.forEach(oldScript => {
            // Создаем новый элемент script
            const newScript = document.createElement('script');
            
            // Копируем все атрибуты
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            
            // Копируем содержимое скрипта
            newScript.textContent = oldScript.textContent;
            
            // Заменяем старый скрипт новым
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
        
        // Регистрируем функцию initFaqAccordion
        window.initFaqAccordion = function() {
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(function (question) {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling;
                    const isOpen = this.classList.contains('active');
                    
                    // Закрываем все вопросы
                    document.querySelectorAll('.faq-question').forEach(q => {
                        q.classList.remove('active');
                        if (q.nextElementSibling) {
                            q.nextElementSibling.style.maxHeight = '0';
                        }
                    });
                    
                    // Если вопрос не был открыт, открываем его
                    if (!isOpen) {
                        this.classList.add('active');
                        answer.style.maxHeight = answer.scrollHeight + 'px';
                    }
                });
            });
            
            // Инициализируем flatpickr
            initFlatpickr();
        };
        
        // Вызываем функцию инициализации FAQ
        setTimeout(() => {
            if (typeof window.initFaqAccordion === 'function') {
                window.initFaqAccordion();
            }
        }, 500);
    }
    
    /**
     * Инициализация flatpickr для полей с датами
     */
    function initFlatpickr() {
        if (typeof flatpickr === 'function') {
            const dateInputs = document.querySelectorAll('.flatpickr-input');
            if (dateInputs.length > 0) {
                dateInputs.forEach(input => {
                    flatpickr(input, {
                        dateFormat: "d.m.Y",
                        locale: {
                            firstDayOfWeek: 1
                        }
                    });
                });
                console.log('Flatpickr initialized for', dateInputs.length, 'inputs');
            }
        }
    }
    
    /**
     * Извлечение информации о серии из HTML содержимого
     * @param {string} html - HTML содержимое
     * @returns {Object} - Информация о серии
     */
    function extractSeriesInfoFromHtml(html) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Извлекаем данные о выпущенных экземплярах
        const seriesQuantityInput = tempDiv.querySelector('[data-editable="series_quantity"]');
        let seriesQuantity = 1;
        
        if (seriesQuantityInput) {
            seriesQuantity = parseInt(seriesQuantityInput.value || seriesQuantityInput.textContent, 10) || 1;
        }
        
        // Извлекаем данные о требуемых сканированиях
        const requiredScansInput = tempDiv.querySelector('[data-editable="required_scans"]');
        let requiredScans = 1;
        
        if (requiredScansInput) {
            requiredScans = parseInt(requiredScansInput.value || requiredScansInput.textContent, 10) || 1;
        }
        
        // Определяем, есть ли поля серии в шаблоне
        const hasSeries = seriesQuantity > 1 || !!seriesQuantityInput || !!requiredScansInput;
        
        return {
            hasSeries,
            seriesQuantity,
            requiredScans
        };
    }
    
    /**
     * Сбор данных из редактируемых полей
     * @returns {Object} - Собранные данные
     */
    function collectEditableFieldsData() {
        const templateContent = document.getElementById('template-content');
        if (!templateContent) return {};
        
        const editableElements = templateContent.querySelectorAll('[data-editable]');
        const collectedData = {};
        
        editableElements.forEach(element => {
            const fieldName = element.getAttribute('data-editable');
            let value;
            
            // Определяем тип элемента и получаем его значение
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                value = element.value;
            } else if (element.tagName === 'SELECT') {
                value = element.value;
            } else {
                value = element.textContent;
            }
            
            // Сохраняем значение в объекте
            collectedData[fieldName] = value;
        });
        
        return collectedData;
    }
    
    /**
     * Обновление данных формы перед сохранением
     * @returns {boolean} - Результат обновления
     */
    function updateFormData() {
        try {
            const templateContent = document.getElementById('template-content');
            if (!templateContent) return false;
            
            // Получаем текущий HTML контент
            const currentHTML = templateContent.innerHTML;
            
            // Обновляем поле html_content
            const htmlContentField = document.getElementById('html_content');
            if (htmlContentField) {
                htmlContentField.value = currentHTML;
            }
            
            // Получаем все данные из редактируемых полей
            const fieldsData = collectEditableFieldsData();
            
            // Объединяем с существующими custom_data
            const updatedCustomData = {...customData, ...fieldsData};
            
            // Проверяем, содержит ли шаблон информацию о серии
            const seriesInfo = extractSeriesInfoFromHtml(currentHTML);
            
            // Если шаблон содержит информацию о серии, добавляем её в custom_data
            if (seriesInfo.hasSeries) {
                updatedCustomData.is_series = true;
                updatedCustomData.series_quantity = seriesInfo.seriesQuantity;
                updatedCustomData.required_scans = seriesInfo.requiredScans;
            }
            
            // Обновляем поле custom_data
            const customDataField = document.getElementById('custom_data');
            if (customDataField) {
                customDataField.value = JSON.stringify(updatedCustomData);
            }
            
            return true;
        } catch (error) {
            console.error('Error updating form data:', error);
            return false;
        }
    }
    
    /**
     * Сохранение шаблона
     * @returns {boolean} - Результат сохранения
     */
    function saveTemplateForm() {
        console.log('Вызвана функция сохранения шаблона');
        
        const templateForm = document.getElementById('template-save-form');
        if (!templateForm) {
            console.error('Форма не найдена: template-save-form');
            return false;
        }
        
        // Убедимся, что метод формы установлен правильно
        templateForm.method = 'POST';
        
        // Обновляем данные формы
        const updateSuccess = updateFormData();
        
        if (updateSuccess) {
            // Показываем индикатор загрузки
            const loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'form-submit-indicator';
            loadingIndicator.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75';
            loadingIndicator.style.zIndex = '2000';
            loadingIndicator.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Сохранение шаблона...</p>
                </div>
            `;
            document.body.appendChild(loadingIndicator);
            
            // Отправляем форму
            templateForm.submit();
            return true;
        } else {
            alert('Произошла ошибка при подготовке данных. Пожалуйста, попробуйте еще раз.');
            return false;
        }
    }
    
    /**
     * Исправление проблем с прокруткой на мобильных устройствах
     */
    function fixMobileScrolling() {
        // Проверяем, является ли устройство мобильным
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Убираем потенциально проблемные стили
            document.querySelectorAll('.fullscreen').forEach(element => {
                element.style.position = 'relative';
                element.style.height = 'auto';
                element.style.minHeight = 'auto';
                element.style.overflow = 'visible';
            });
            
            // Удаляем строки, устанавливающие overflow и height для body
            // document.body.style.overflow = 'auto';
            // document.body.style.height = 'auto';
            document.documentElement.style.overflow = 'auto';
            document.documentElement.style.height = 'auto';
            
            // Добавляем правильную обработку сенсорных событий для редактируемых элементов
            document.querySelectorAll('[data-editable]').forEach(element => {
                element.style.touchAction = 'manipulation';
                
                // Предотвращаем конфликты событий touch
                element.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                }, { passive: true });
            });
            
            // Предотвращаем любые обработчики, которые могут блокировать скролл
            const preventScrollBlock = function(e) {
                e.stopPropagation();
            };
            
            document.addEventListener('touchmove', function(e) {
                // Разрешаем стандартное поведение скролла
                if (e.touches.length === 1) {
                    e.stopPropagation();
                }
            }, { passive: true });
            
            // Удаляем периодическую установку overflow для body
            // const scrollCheckInterval = setInterval(function() {
            //     document.body.style.overflow = 'auto';
            //     document.body.style.height = 'auto';
            // }, 1000);
            
            // // Очистка интервала при уходе со страницы
            // window.addEventListener('beforeunload', function() {
            //     clearInterval(scrollCheckInterval);
            // });
        }
    }
    
    // Публичные методы
    return {
        init,
        saveTemplateForm
    };
})();
