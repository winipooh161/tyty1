<script>
document.addEventListener('DOMContentLoaded', function() {
    const templatePreview = document.getElementById('template-preview');
    const templateLoading = document.getElementById('template-loading');
    const templateContent = document.getElementById('template-content');
    const templateForm = document.getElementById('template-save-form');
    const saveButton = document.getElementById('save-template-btn');
    
    // Загружаем необходимые внешние библиотеки
    loadRequiredLibraries(['flatpickr'], function() {
        // После загрузки библиотек загружаем контент шаблона
        loadTemplateContent();
    });
    
    // Функция для загрузки внешних библиотек
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
        const toLoad = libraries.filter(lib => {
            if (lib === 'flatpickr' && typeof window.flatpickr === 'function') {
                loadedCount++;
                return false;
            }
            return true;
        });
        
        // Если все библиотеки загружены, вызываем callback
        if (loadedCount === requiredCount) {
            callback();
            return;
        }
        
        // Загружаем необходимые библиотеки
        toLoad.forEach(lib => {
            if (lib === 'flatpickr') {
                // Проверяем, загружена ли уже библиотека
                if (typeof window.flatpickr === 'function') {
                    checkAllLoaded();
                    return;
                }
                
                // Загружаем CSS для flatpickr
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
                document.head.appendChild(link);
                
                // Загружаем JavaScript для flatpickr
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
                script.onload = function() {
                    console.log('Flatpickr loaded');
                    
                    // Загружаем русскую локализацию
                    const localeScript = document.createElement('script');
                    localeScript.src = 'https://npmcdn.com/flatpickr/dist/l10n/ru.js';
                    localeScript.onload = function() {
                        console.log('Flatpickr Russian locale loaded');
                        checkAllLoaded();
                    };
                    document.head.appendChild(localeScript);
                };
                script.onerror = function() {
                    console.error('Failed to load flatpickr');
                    checkAllLoaded();
                };
                document.head.appendChild(script);
            }
        });
        
        function checkAllLoaded() {
            loadedCount++;
            if (loadedCount >= requiredCount) {
                callback();
            }
        }
    }
    
    // Регистрируем компоненты шаблона в глобальном объекте
    window.TemplateManager = {
        // Инициализация FAQ аккордеона
        initFaqAccordion: function() {
            console.log('Template Manager: initFaqAccordion called');
            
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach((question, index) => {
                // Проверяем, был ли уже добавлен обработчик
                if (question.getAttribute('data-init') === 'true') {
                    return;
                }
                
                question.setAttribute('data-init', 'true');
                
                question.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const faqItem = question.closest('.faq-item');
                    const faqAnswer = faqItem.querySelector('.faq-answer');
                    
                    if (!faqItem || !faqAnswer) return;
                    
                    // Закрываем другие открытые FAQ
                    document.querySelectorAll('.faq-item.active').forEach(activeItem => {
                        if (activeItem !== faqItem) {
                            activeItem.classList.remove('active');
                            const activeAnswer = activeItem.querySelector('.faq-answer');
                            if (activeAnswer) {
                                activeAnswer.style.maxHeight = null;
                                activeAnswer.style.padding = '0 15px';
                            }
                        }
                    });
                    
                    const wasActive = faqItem.classList.contains('active');
                    faqItem.classList.toggle('active');
                    
                    if (wasActive) {
                        faqAnswer.style.maxHeight = null;
                        faqAnswer.style.padding = '0 15px';
                    } else {
                        faqAnswer.style.maxHeight = faqAnswer.scrollHeight + 'px';
                        faqAnswer.style.padding = '0';
                    }
                });
                
                // Применяем начальное состояние, если FAQ активен
                const faqItem = question.closest('.faq-item');
                if (faqItem && faqItem.classList.contains('active')) {
                    const answer = faqItem.querySelector('.faq-answer');
                    if (answer) {
                        answer.style.maxHeight = answer.scrollHeight + 'px';
                        answer.style.padding = '0';
                    }
                }
            });
            
            // Инициализируем датапикеры
            this.initDatePickers();
        },
        
        // Инициализация выбора дат
        initDatePickers: function() {
            if (typeof flatpickr !== 'function') {
                console.warn('Flatpickr не загружен!');
                return;
            }
            
            document.querySelectorAll('.issue-date-s, .issue-date-do').forEach(element => {
                if (element.getAttribute('data-flatpickr-initialized')) return;
                
                const textPrefix = element.classList.contains('issue-date-s') ? 'с: ' : 'до: ';
                
                try {
                    const fp = flatpickr(element, {
                        locale: "ru",
                        dateFormat: "d F Y г.",
                        defaultDate: element.textContent.replace(/^(с: |до: )/, ''),
                        disableMobile: true,
                        allowInput: false,
                        appendTo: document.body,
                        onChange: function(selectedDates, dateStr) {
                            if (!dateStr) return;
                            element.textContent = textPrefix + dateStr;
                            element.dataset.date = dateStr;
                        }
                    });
                    
                    element.setAttribute('data-flatpickr-initialized', 'true');
                    
                    // Отключаем стандартное редактирование
                    if (element.tagName !== 'INPUT') {
                        element.contentEditable = false;
                    }
                    
                    element.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (fp) fp.open();
                    });
                } catch (error) {
                    console.error('Ошибка инициализации датапикера:', error);
                }
            });
            
            // Скрываем лишние input-элементы
            document.querySelectorAll('input.flatpickr-input').forEach(input => {
                input.style.display = 'none';
                input.tabIndex = -1;
            });
        },
        
        // Обработка ссылок в тексте
        processLinks: function() {
            const contactField = document.querySelector('[data-editable="faq_answer_1"]');
            if (!contactField) return;
            
            contactField.innerHTML = this.linkify(contactField.textContent || contactField.innerText);
            contactField.classList.add('auto-links', 'preserve-whitespace');
            
            contactField.addEventListener('focus', function() {
                this.dataset.originalContent = this.innerHTML;
                let plainText = this.innerHTML.replace(/<br\s*\/?>/gi, '\n');
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = plainText;
                plainText = tempDiv.textContent || tempDiv.innerText;
                this.textContent = plainText;
            });
            
            contactField.addEventListener('blur', function() {
                const rawText = this.textContent || '';
                this.innerHTML = TemplateManager.linkify(rawText);
            });
            
            contactField.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const selection = window.getSelection();
                    const range = selection.getRangeAt(0);
                    const textNode = document.createTextNode('\n');
                    range.insertNode(textNode);
                    range.setStartAfter(textNode);
                    range.setEndAfter(textNode);
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            });
        },
        
        // Конвертация текста в ссылки
        linkify: function(text) {
            if (!text) return '';
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = text;
            let cleanText = tempDiv.textContent;
            
            cleanText = cleanText.replace(/\n/g, '<br>');
            
            const patterns = {
                url: /(https?:\/\/[^\s<]+)|(?<=\s|^)(www\.[^\s<]+)/g,
                email: /([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+)/g,
                vk: /(?:(?:Вконтакте|Vkontakte|VK|ВК|Вк):?\s*)(?:(https?:\/\/(?:vk\.com|m\.vk\.com)\/[^\s<]+)|@?([a-zA-Z0-9._]+))/gi,
                telegram: /(?:(?:Телеграм|Telegram|тг|телега):?\s*)(?:(https?:\/\/t\.me\/[^\s<]+)|@([a-zA-Z0-9._]+))/gi,
                whatsapp: /(?:(?:WhatsApp|ВатсАп|вотсап):?\s*)(?:(https?:\/\/(?:wa\.me|api\.whatsapp\.com\/send)\?[^\s<]+)|(?:\+([0-9]+)))/gi,
                phone: /(?:(?:тел(?:ефон)?|tel|phone|номер):?\s*)?(?:\+7|8)[- ]?\(?(\d{3})\)?[- ]?(\d{3})[- ]?(\d{2})[- ]?(\d{2})/g,
                viber: /(?:(?:Viber|Вайбер):?\s*)(?:(https?:\/\/(?:viber|vb)\.me\/[^\s<]+)|(?:\+([0-9]+)))/gi,
                instagram: /(?:(?:Instagram|Инстаграм|инста|Threads|Тредс):?\s*)(?:(https?:\/\/(?:www\.)?instagram\.com\/[^\s<]+)|@([a-zA-Z0-9._]+))/gi,
            };
            
            // Форматирование телефона
            const formatPhone = (match, p1, p2, p3, p4) => {
                const cleaned = (p1 || '') + (p2 || '') + (p3 || '') + (p4 || '');
                if (cleaned.length !== 10) return match;
                
                const formatted = `+7 (${p1}) ${p2}-${p3}-${p4}`;
                const phoneUrl = `tel:+7${p1}${p2}${p3}${p4}`;
                return `<a href="${phoneUrl}" class="phone-link">${formatted}</a>`;
            };
            
            // Обрабатываем телефоны
            cleanText = cleanText.replace(/(?:\+7|8)[- ]?\(?(\d{3})\)?[- ]?(\d{3})[- ]?(\d{2})[- ]?(\d{2})/g, formatPhone);
            
            // Обрабатываем URL
            cleanText = cleanText.replace(patterns.url, function(match) {
                const url = match.startsWith('www.') ? 'https://' + match : match;
                return `<a href="${url}" target="_blank" rel="noopener noreferrer">${match}</a>`;
            });
            
            // Обрабатываем email
            cleanText = cleanText.replace(patterns.email, '<a href="mailto:$1" class="email-link">$1</a>');
            
            // Обрабатываем соц. сети
            cleanText = cleanText.replace(patterns.vk, function(match, url, username) {
                if (url) return `ВКонтакте: <a href="${url}" target="_blank" class="social-link vk-link">${url}</a>`;
                if (username) {
                    const vkUrl = `https://vk.com/${username.replace('@', '')}`;
                    return `ВКонтакте: <a href="${vkUrl}" target="_blank" class="social-link vk-link">@${username.replace('@', '')}</a>`;
                }
                return match;
            });
            
            // Обрабатываем Telegram
            cleanText = cleanText.replace(patterns.telegram, function(match, url, username) {
                if (url) return `Telegram: <a href="${url}" target="_blank" class="social-link telegram-link">${url}</a>`;
                if (username) {
                    const tgUrl = `https://t.me/${username.replace('@', '')}`;
                    return `Telegram: <a href="${tgUrl}" target="_blank" class="social-link telegram-link">@${username.replace('@', '')}</a>`;
                }
                return match;
            });
            
            // Аналогичная обработка для WhatsApp, Viber, Instagram
            cleanText = cleanText.replace(patterns.whatsapp, function(match, url, phone) {
                if (url) return `WhatsApp: <a href="${url}" target="_blank" class="social-link whatsapp-link">${url}</a>`;
                if (phone) {
                    const waUrl = `https://wa.me/${phone}`;
                    return `WhatsApp: <a href="${waUrl}" target="_blank" class="social-link whatsapp-link">+${phone}</a>`;
                }
                return match;
            });
            
            cleanText = cleanText.replace(patterns.viber, function(match, url, phone) {
                if (url) return `Viber: <a href="${url}" target="_blank" class="social-link viber-link">${url}</a>`;
                if (phone) {
                    const viberUrl = `viber://chat?number=${phone}`;
                    return `Viber: <a href="${viberUrl}" class="social-link viber-link">+${phone}</a>`;
                }
                return match;
            });
            
            cleanText = cleanText.replace(patterns.instagram, function(match, url, username) {
                if (url) return `Instagram: <a href="${url}" target="_blank" class="social-link instagram-link">${url}</a>`;
                if (username) {
                    const instaUrl = `https://www.instagram.com/${username.replace('@', '')}`;
                    return `Instagram: <a href="${instaUrl}" target="_blank" class="social-link instagram-link">@${username.replace('@', '')}</a>`;
                }
                return match;
            });
            
            return cleanText;
        }
    };
    
    // Экспортируем для совместимости с существующим кодом
    window.initFaqAccordion = function() {
        window.TemplateManager.initFaqAccordion();
    };
    
    window.linkify = function(text) {
        return window.TemplateManager.linkify(text);
    };
    
    // Функция для загрузки HTML-контента шаблона
    function loadTemplateContent() {
        // Показываем индикатор загрузки
        templateLoading.style.display = 'flex';
        templateContent.style.display = 'none';
        
        // Получаем HTML-контент из серверного кэша или базы данных
        let htmlContent = '';
        
        <?php if(isset($userTemplate)): ?>
            htmlContent = <?php echo json_encode($userTemplate->html_content); ?>;
        <?php else: ?>
            htmlContent = <?php echo json_encode($template->html_content); ?>;
        <?php endif; ?>
        
        // Устанавливаем HTML-контент с небольшой задержкой для отображения индикатора загрузки
        setTimeout(() => {
            // Устанавливаем HTML в контейнер
            templateContent.innerHTML = htmlContent;
            
            // Скрываем индикатор загрузки и показываем контент
            templateLoading.style.display = 'none';
            templateContent.style.display = 'block';
            
            // Инициализируем редактируемые элементы
            initializeEditableElements();
            
            // Обновляем данные формы
            updateFormData();
            
            // Выполняем скрипты вручную
            executeScripts();
            
            console.log('Template content loaded successfully');
        }, 100);
    }
    
    // Функция для выполнения скриптов внутри загруженного HTML
    function executeScripts() {
        // Найти все скрипты в загруженном контенте
        const scripts = templateContent.querySelectorAll('script');
        
        scripts.forEach(oldScript => {
            try {
                // Создаем новый элемент script
                const newScript = document.createElement('script');
                
                // Копируем атрибуты
                Array.from(oldScript.attributes).forEach(attr => {
                    newScript.setAttribute(attr.name, attr.value);
                });
                
                // Если это внешний скрипт, добавляем обработчик загрузки
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                    newScript.onload = function() {
                        console.log('External script loaded:', oldScript.src);
                        // После загрузки внешнего скрипта проверяем инициализацию
                        if (typeof window.TemplateManager?.initFaqAccordion === 'function') {
                            setTimeout(window.TemplateManager.initFaqAccordion.bind(window.TemplateManager), 100);
                        } else if (typeof window.initFaqAccordion === 'function') {
                            setTimeout(window.initFaqAccordion, 100);
                        }
                    };
                } else {
                    // Для встроенных скриптов копируем содержимое
                    newScript.textContent = oldScript.textContent;
                }
                
                // Удаляем старый скрипт и добавляем новый
                oldScript.parentNode.replaceChild(newScript, oldScript);
                
                console.log('Script executed successfully');
            } catch (error) {
                console.error('Error executing script:', error);
            }
        });
        
        // Проверяем наличие функции инициализации FAQ и вызываем её
        if (typeof window.TemplateManager?.initFaqAccordion === 'function') {
            setTimeout(() => {
                window.TemplateManager.initFaqAccordion.call(window.TemplateManager);
                console.log('FAQ accordion initialized');
            }, 500);
        } else if (typeof window.initFaqAccordion === 'function') {
            setTimeout(() => {
                window.initFaqAccordion();
                console.log('FAQ accordion initialized (legacy)');
            }, 500);
        }
    }
    
    // Получение пользовательских данных
    let customData = {};
    try {
        const customDataInput = document.getElementById('custom_data');
        if (customDataInput && customDataInput.value) {
            customData = JSON.parse(customDataInput.value);
        }
    } catch (e) {
        console.error('Ошибка при парсинге пользовательских данных', e);
    }
    
    // Функция для извлечения информации о серии из HTML содержимого
    function extractSeriesInfoFromHtml(html) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Извлекаем данные о выпущенных экземплярах
        const seriesQuantityInput = tempDiv.querySelector('[data-editable="series_quantity"]');
        let seriesQuantity = 1;
        
        if (seriesQuantityInput) {
            if (seriesQuantityInput.tagName === 'INPUT') {
                seriesQuantity = parseInt(seriesQuantityInput.value || seriesQuantityInput.placeholder || '1');
            } else {
                seriesQuantity = parseInt(seriesQuantityInput.textContent.trim() || '1');
            }
        }
        
        // Извлекаем данные о требуемых сканированиях
        const requiredScansInput = tempDiv.querySelector('[data-editable="required_scans"]');
        let requiredScans = 1;
        
        if (requiredScansInput) {
            if (requiredScansInput.tagName === 'INPUT') {
                requiredScans = parseInt(requiredScansInput.value || requiredScansInput.placeholder || '1');
            } else {
                requiredScans = parseInt(requiredScansInput.textContent.trim() || '1');
            }
        }
        
        // Определяем, есть ли поля серии в шаблоне (если quantity > 1 или есть сами поля)
        const hasSeries = seriesQuantity > 1 || !!seriesQuantityInput || !!requiredScansInput;
        
        return {
            is_series: hasSeries,
            series_quantity: seriesQuantity,
            required_scans: requiredScans
        };
    }
    
    // Сбор данных из редактируемых полей
    function collectEditableFieldsData() {
        const editableElements = templatePreview.querySelectorAll('[data-editable]');
        const collectedData = {};
        
        editableElements.forEach(element => {
            const fieldName = element.dataset.editable;
            let value;
            
            if (element.tagName === 'INPUT') {
                value = element.value;
                
                // Для числовых полей серии конвертируем в число
                if (['series_quantity', 'series_received', 'scan_count', 'required_scans'].includes(fieldName)) {
                    value = parseInt(value) || (fieldName === 'series_quantity' || fieldName === 'required_scans' ? 1 : 0);
                }
            } else {
                value = element.innerHTML;
            }
            
            collectedData[fieldName] = value;
        });
        
        return collectedData;
    }
    
    // Обновление данных формы
    function updateFormData() {
        try {
            // Собираем данные из всех редактируемых полей
            const editableFieldsData = collectEditableFieldsData();
            
            // Получаем HTML содержимое
            const htmlContent = templatePreview.innerHTML;
            
            // Извлекаем информацию о серии из HTML
            const seriesInfo = extractSeriesInfoFromHtml(htmlContent);
            
            // Объединяем все данные, приоритет у собранных данных полей
            const updatedCustomData = {
                ...customData,
                ...seriesInfo,
                ...editableFieldsData // editableFieldsData имеет наивысший приоритет
            };
            
            // Дополнительная проверка и корректировка данных серии
            if (updatedCustomData.series_quantity && updatedCustomData.series_quantity > 1) {
                updatedCustomData.is_series = true;
            }
            
            // Обновляем поля формы
            document.getElementById('html_content').value = htmlContent;
            document.getElementById('custom_data').value = JSON.stringify(updatedCustomData);
            
            // Обновляем название шаблона, если есть поле с названием
            const titleField = templatePreview.querySelector('[data-editable="certificate_title"]');
            if (titleField) {
                const title = titleField.tagName === 'INPUT' ? titleField.value : titleField.textContent;
                if (title && title.trim()) {
                    document.getElementById('template-name').value = title.trim();
                }
            }
            
            return true;
        } catch (error) {
            console.error('Error updating form data:', error);
            return false;
        }
    }
    
    // Обработчик клика по кнопке сохранения
    saveButton.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Показываем индикатор загрузки
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Сохранение...';
        
        // Обновляем данные формы
        const updateSuccess = updateFormData();
        
        if (updateSuccess) {
            // Отправляем форму
            templateForm.submit();
        } else {
            // Возвращаем кнопку в исходное состояние при ошибке
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="bi bi-check-circle me-2"></i>Сохранить шаблон';
            alert('Произошла ошибка при подготовке данных для сохранения. Попробуйте еще раз.');
        }
    });
    
    // Инициализация элементов шаблона для редактирования
    function initializeEditableElements() {
        const editableElements = templateContent.querySelectorAll('[data-editable]');
        
        editableElements.forEach(element => {
            const fieldName = element.dataset.editable;
            
            // Сохраняем исходное содержимое
            if (!element.dataset.defaultContent) {
                if (element.tagName === 'INPUT') {
                    element.dataset.defaultContent = element.value || element.placeholder;
                } else {
                    element.dataset.defaultContent = element.innerHTML;
                }
            }
            
            // Если есть пользовательские данные, устанавливаем их
            if (customData[fieldName] !== undefined) {
                if (element.tagName === 'INPUT') {
                    element.value = customData[fieldName];
                } else {
                    element.innerHTML = customData[fieldName];
                }
            }
            
            // Добавляем обработчики событий
            element.addEventListener('click', function(e) {
                e.stopPropagation();
                focusAndEnableEditing(this);
            });
            
            element.addEventListener('blur', function() {
                this.contentEditable = false;
                this.classList.remove('editing');
                
                // Обновляем данные при завершении редактирования
                const fieldName = this.dataset.editable;
                let newValue;
                
                if (this.tagName === 'INPUT') {
                    newValue = this.value;
                    // Для числовых полей серии конвертируем в число
                    if (['series_quantity', 'series_received', 'scan_count', 'required_scans'].includes(fieldName)) {
                        newValue = parseInt(newValue) || (fieldName === 'series_quantity' || fieldName === 'required_scans' ? 1 : 0);
                        this.value = newValue; // Обновляем отображаемое значение
                    }
                } else {
                    newValue = this.innerHTML;
                }
                
                customData[fieldName] = newValue;
                
                // Автоматически обновляем форму при каждом изменении
                updateFormData();
            });
            
            // Обработка input события для input элементов
            if (element.tagName === 'INPUT') {
                element.addEventListener('input', function() {
                    const fieldName = this.dataset.editable;
                    let value = this.value;
                    
                    // Для числовых полей серии конвертируем в число
                    if (['series_quantity', 'series_received', 'scan_count', 'required_scans'].includes(fieldName)) {
                        value = parseInt(value) || (fieldName === 'series_quantity' || fieldName === 'required_scans' ? 1 : 0);
                    }
                    
                    customData[fieldName] = value;
                });
            }
            
            // Обработка нажатия клавиш
            element.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.blur();
                }
                if (e.key === 'Escape') {
                    // Отмена изменений
                    const fieldName = this.dataset.editable;
                    if (customData[fieldName] !== undefined) {
                        if (this.tagName === 'INPUT') {
                            this.value = customData[fieldName];
                        } else {
                            this.innerHTML = customData[fieldName];
                        }
                    } else {
                        if (this.tagName === 'INPUT') {
                            this.value = this.dataset.defaultContent || '';
                        } else {
                            this.innerHTML = this.dataset.defaultContent || '';
                        }
                    }
                    this.blur();
                }
            });
        });
    }
    
    // Фокусировка и активация режима редактирования
    function focusAndEnableEditing(element) {
        // Снимаем выделение с других элементов
        document.querySelectorAll('[data-editable].editing').forEach(el => {
            if (el !== element) {
                el.classList.remove('editing');
                if (el.tagName !== 'INPUT') {
                    el.contentEditable = false;
                }
            }
        });
        
        // Переключаем режим редактирования
        element.classList.add('editing');
        
        if (element.tagName === 'INPUT') {
            element.focus();
            element.select();
        } else {
            element.contentEditable = true;
            element.focus();
        }
    }
    
    // Обработчик для управления обложкой
    function initCoverHandlers() {
        const coverContainer = document.getElementById('coverPreviewContainer');
        const returnToCover = document.getElementById('returnToCover');
        const toggleCoverBtn = document.getElementById('toggleCoverBtn');
        const skipBtnText = document.getElementById('skipBtnText');
        
        let isCoverHidden = false;
        
        // Функция для переключения видимости обложки
        function toggleCover() {
            if (isCoverHidden) {
                // Показываем обложку
                coverContainer.classList.remove('cover-hidden');
                skipBtnText.textContent = 'Перейти к редактированию';
                toggleCoverBtn.querySelector('i').classList.remove('bi-chevron-up');
                toggleCoverBtn.querySelector('i').classList.add('bi-chevron-down');
            } else {
                // Скрываем обложку
                coverContainer.classList.add('cover-hidden');
                skipBtnText.textContent = 'Показать обложку';
                toggleCoverBtn.querySelector('i').classList.remove('bi-chevron-down');
                toggleCoverBtn.querySelector('i').classList.add('bi-chevron-up');
            }
            isCoverHidden = !isCoverHidden;
        }
        
        // Обработчик клика по кнопке
        if (toggleCoverBtn) {
            toggleCoverBtn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleCover();
            });
        }
        
        // Обработчик клика по индикатору возврата
        if (returnToCover) {
            returnToCover.addEventListener('click', function() {
                if (isCoverHidden) {
                    toggleCover();
                }
            });
        }
    }
    
    // Инициализируем обработчики обложки
    initCoverHandlers();
    
    // Автоматически воспроизводим видео при загрузке
    const coverVideo = document.getElementById('coverVideo');
    if (coverVideo) {
        coverVideo.play().catch(error => {
            console.log("Автовоспроизведение видео не поддерживается");
        });
    }
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/components/editor-scripts.blade.php ENDPATH**/ ?>