<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/davidshimjs-qrcodejs@0.0.2/qrcode.min.js"></script>

<script>
    /**
     * TemplateViewer - Модуль для просмотра шаблонов на публичной странице
     * Обеспечивает работу с QR-кодами, инициализацию шаблонных скриптов и автозаполнение данных
     */
    class TemplateViewer {
        constructor(config) {
            this.config = {
                qrCodeSelector: '#qrcode',
                qrLoadingSelector: '#qr-loading',
                templateId: {{ $userTemplate->id }},
                userId: {{ Auth::check() ? Auth::id() : 'null' }},
                acquiredId: {{ isset($acquiredTemplate) && $acquiredTemplate ? $acquiredTemplate->id : 0 }},
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                baseUrl: '{{ url("/") }}',
                ...config
            };
            
            // Инициализируем данные серии с улучшенной обработкой null
            const seriesDataFromServer = @json($seriesData ?? null);
            this.seriesData = seriesDataFromServer || {
                'acquired_count': 0, 
                'scan_count': 0, 
                'series_quantity': 1, 
                'required_scans': 1,
                'is_series': false
            };
            
            // Логируем данные о серии для диагностики
            console.log('Series data initialized:', this.seriesData);
        }
        
        init() {
            this.handleCoverMedia();
            this.generateQrCode();
            this.fillUserData();
            this.fillSeriesData();
            this.initTemplateScripts();
            this.setupAcquireFormHandler();
            this.checkForTruncatedScripts();
            
            console.log('TemplateViewer initialized');
        }
        
        // Обрабатывает мультимедиа элементы обложки
        handleCoverMedia() {
            const coverVideo = document.getElementById('coverVideo');
            if (coverVideo) {
                coverVideo.play().catch(error => {
                    console.log("Автовоспроизведение видео не поддерживается");
                });
            }
        }
        
        // Генерирует QR-код для шаблона
        generateQrCode() {
            const qrCodeContainer = document.querySelector(this.config.qrCodeSelector);
            const qrLoadingContainer = document.querySelector(this.config.qrLoadingSelector);
            
            if (!qrCodeContainer) return;
            
            if (qrLoadingContainer) {
                qrLoadingContainer.style.display = 'block';
            }
            
            setTimeout(() => {
                try {
                    if (this.config.userId) {
                        const timestamp = Math.floor(Date.now() / 1000);
                        const nonce = '{{ Str::random(10) }}';
                        const changeStatusUrl = `${this.config.baseUrl}/template-status/change/${this.config.templateId}/${this.config.userId}/${this.config.acquiredId}/${timestamp}?_token=${this.config.csrfToken}&nonce=${nonce}`;
                        
                        if (qrCodeContainer) {
                            qrCodeContainer.innerHTML = '';
                            
                            new QRCode(qrCodeContainer, {
                                text: changeStatusUrl,
                                width: 160,
                                height: 160,
                                colorDark: "#000000",
                                colorLight: "#ffffff",
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        }
                    } else {
                        if (qrCodeContainer) {
                            qrCodeContainer.innerHTML = `
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    Войдите в систему, чтобы увидеть QR-код
                                </div>
                            `;
                        }
                    }
                } catch (error) {
                    console.error('Ошибка при генерации QR-кода', error);
                } finally {
                    if (qrLoadingContainer) {
                        setTimeout(() => {
                            qrLoadingContainer.style.display = 'none';
                        }, 300);
                    }
                }
            }, 400);
        }
        
        // Заполняет данные пользователя в шаблоне
        fillUserData() {
            @auth
            const recipientNameFields = document.querySelectorAll('[data-editable="recipient_name"]');
            
            if (recipientNameFields.length > 0) {
                const userName = @json(Auth::user()->name);
                
                recipientNameFields.forEach(field => {
                    field.innerHTML = userName;
                });
            }
            @endauth
        }
        
        // Заполняет данные о серии шаблона с улучшенной обработкой ошибок
        fillSeriesData() {
            setTimeout(() => {
                try {
                    // Подставляем значения в соответствующие поля
                    this.updateSeriesField('series_quantity', this.seriesData.series_quantity || 1);
                    this.updateSeriesField('series_received', this.seriesData.acquired_count || 0);
                    this.updateSeriesField('scan_count', this.seriesData.scan_count || 0);
                    this.updateSeriesField('required_scans', this.seriesData.required_scans || 1);
                    
                    // Обновляем комбинированное отображение для сканирований
                    this.updateScanProgressDisplay();
                    
                    // Добавляем визуальную индикацию для серийных шаблонов
                    if (this.seriesData.series_quantity > 1 || this.seriesData.is_series) {
                        document.querySelectorAll('.certificate-series-info').forEach(el => {
                            el.classList.add('series-active');
                        });
                    }
                    
                    // Дополнительная обработка шаблонных тегов
                    this.processTemplateFields();
                    
                    console.log('Series data filled successfully:', this.seriesData);
                } catch (error) {
                    console.error('Error filling series data:', error);
                }
            }, 500);
        }
        
        // Обновляет отображение прогресса сканирований
        updateScanProgressDisplay() {
            const scanProgressDisplay = document.querySelector('.scan-progress-display');
            if (scanProgressDisplay) {
                const scanCount = this.seriesData.scan_count || 0;
                const requiredScans = this.seriesData.required_scans || 1;
                scanProgressDisplay.value = `${scanCount} / ${requiredScans}`;
                
                // Добавляем классы для визуальной индикации прогресса
                if (scanCount >= requiredScans) {
                    scanProgressDisplay.classList.add('scans-complete');
                } else if (scanCount > 0) {
                    scanProgressDisplay.classList.add('scans-in-progress');
                }
            }
        }
        
        // Обновляет значение поля серии
        updateSeriesField(fieldName, value) {
            const fields = document.querySelectorAll(`[data-editable="${fieldName}"]`);
            
            fields.forEach(field => {
                console.log(`Updating field ${fieldName} with value ${value}`, field);
                
                if (field.tagName === 'INPUT') {
                    field.value = value;
                    field.placeholder = value;
                    field.setAttribute('value', value);
                } else {
                    field.textContent = value;
                }
            });
        }
        
        // Обрабатывает поля шаблона с шаблонными тегами Blade
        processTemplateFields() {
            document.querySelectorAll('[data-editable]').forEach(element => {
                const fieldName = element.getAttribute('data-editable');
                
                // Проверяем, есть ли в тексте шаблонные теги Blade
                if (element.innerText && element.innerText.includes('{{')) {
                    if (this.seriesData[fieldName] !== undefined) {
                        if (element.tagName === 'INPUT') {
                            element.value = this.seriesData[fieldName];
                        } else {
                            element.innerText = this.seriesData[fieldName];
                        }
                    }
                }
                
                // Аналогично для value у input элементов
                if (element.tagName === 'INPUT' && element.value && element.value.includes('{{')) {
                    if (this.seriesData[fieldName] !== undefined) {
                        element.value = this.seriesData[fieldName];
                    }
                }
            });
        }
        
        // Инициализирует скрипты из шаблона
        initTemplateScripts() {
            this.loadTemplateContent();
            
            // Проверяем, доступен ли уже объект TemplateCore
            if (typeof window.TemplateCore === 'object') {
                console.log('TemplateCore уже доступен, инициализируем компоненты');
                if (typeof window.TemplateCore.initFaqAccordion === 'function') {
                    setTimeout(window.TemplateCore.initFaqAccordion, 500);
                }
                if (typeof window.TemplateCore.initDatePickers === 'function') {
                    setTimeout(window.TemplateCore.initDatePickers, 500);
                }
                if (typeof window.TemplateCore.processLinks === 'function') {
                    setTimeout(window.TemplateCore.processLinks, 500);
                }
            } else {
                // Если TemplateCore недоступен, ожидаем его инициализации
                this.waitForTemplateCore();
            }
        }
        
        // Ожидает инициализацию компонентов шаблона
        waitForTemplateCore(attempts = 0) {
            if (attempts > 10) {
                console.warn('Не удалось дождаться инициализации TemplateCore');
                return;
            }
            
            setTimeout(() => {
                if (typeof window.TemplateCore === 'object') {
                    console.log('TemplateCore обнаружен, инициализируем компоненты');
                    if (typeof window.TemplateCore.initFaqAccordion === 'function') {
                        window.TemplateCore.initFaqAccordion();
                    }
                    if (typeof window.TemplateCore.initDatePickers === 'function') {
                        window.TemplateCore.initDatePickers();
                    }
                    if (typeof window.TemplateCore.processLinks === 'function') {
                        window.TemplateCore.processLinks();
                    }
                } else {
                    this.waitForTemplateCore(attempts + 1);
                }
            }, 300);
        }
        
        // Загружает HTML контент шаблона
        loadTemplateContent() {
            const templateLoading = document.getElementById('template-loading');
            const templateHtmlContent = document.getElementById('template-html-content');
            
            if (!templateLoading || !templateHtmlContent) return;
            
            setTimeout(() => {
                templateLoading.style.display = 'none';
                templateHtmlContent.style.display = 'block';
                this.executeScriptsInTemplate(templateHtmlContent);
            }, 100);
        }
        
        // Проверяет и вызывает функции инициализации шаблона
        checkInitFunctions() {
            setTimeout(() => {
                // Проверяем наличие функций инициализации и вызываем их
                if (typeof window.initFaqAccordion === 'function') {
                    window.initFaqAccordion();
                }
                
                if (typeof window.linkify === 'function') {
                    const faqAnswer = document.querySelector('[data-editable="faq_answer_1"]');
                    if (faqAnswer) {
                        faqAnswer.innerHTML = window.linkify(faqAnswer.textContent || faqAnswer.innerText);
                    }
                }
                
                // Инициализируем выбор даты, если доступно
                if (typeof window.initTemplateDatePickers === 'function') {
                    window.initTemplateDatePickers();
                } else if (typeof flatpickr === 'function') {
                    this.initDatePickers();
                }
                
                console.log('Template initialization functions checked and executed');
            }, 300);
        }
        
        // Инициализирует выбор даты в шаблоне
        initDatePickers() {
            const dateElements = document.querySelectorAll('.issue-date-s, .issue-date-do');
            
            if (dateElements.length > 0) {
                console.log('Found date elements, initializing flatpickr');
                
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
            }
        }
        
        // Выполняет скрипты внутри шаблона с проверкой на обрезание
        executeScriptsInTemplate(container) {
            if (!container) return;
            
            const scripts = container.querySelectorAll('script');
            let hasObviousTruncation = false;
            
            scripts.forEach(oldScript => {
                // Проверяем на признаки обрезания
                if ((oldScript.textContent || '').includes('…') || 
                    (oldScript.textContent || '').endsWith('addEven')) {
                    hasObviousTruncation = true;
                    return;
                }
                
                const newScript = document.createElement('script');
                
                Array.from(oldScript.attributes).forEach(attr => {
                    newScript.setAttribute(attr.name, attr.value);
                });
                
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                    newScript.onload = () => {
                        console.log('External script loaded:', oldScript.src);
                        this.checkInitFunctions();
                    };
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
            
            // Если обнаружены обрезанные скрипты, загружаем полную версию
            if (hasObviousTruncation) {
                console.log('Обнаружены обрезанные скрипты при выполнении');
                setTimeout(() => this.loadFullTemplateScript(), 300);
            }
        }
        
        // Проверяет наличие обрезанных скриптов
        checkForTruncatedScripts() {
            const htmlContent = document.getElementById('template-html-content');
            if (!htmlContent) return;
            
            const scripts = htmlContent.querySelectorAll('script');
            let truncated = false;
            
            scripts.forEach(script => {
                const content = script.textContent || '';
                if (
                    content.includes('addEven…') || 
                    content.includes('…') || 
                    content.endsWith('addEven') ||
                    (content.includes('function() {') && !content.includes('function() { }') && content.split('function(').length !== content.split('})').length)
                ) {
                    truncated = true;
                    console.warn('Обнаружен обрезанный скрипт:', content.substring(0, 50) + '...');
                }
            });
            
            if (truncated) {
                console.log('Загружаем полную версию скрипта...');
                this.loadFullTemplateScript();
            }
        }
        
        // Загружает полную версию скрипта шаблона
        loadFullTemplateScript() {
            if (document.querySelector('script[src*="template-full.js"]')) {
                console.log('Полная версия скрипта уже загружена');
                return;
            }
            
            const script = document.createElement('script');
            script.src = '/js/template-full.js?v=' + new Date().getTime();
            script.onload = () => {
                console.log('Полная версия скрипта загружена успешно');
                
                // Инициализируем компоненты после загрузки
                if (window.TemplateJS && typeof window.TemplateJS.init === 'function') {
                    setTimeout(() => {
                        window.TemplateJS.init({
                            debug: true,
                            mode: 'view'
                        });
                        
                        // Дополнительно инициализируем компоненты
                        if (typeof window.TemplateJS.initAccordion === 'function') {
                            window.TemplateJS.initAccordion();
                        }
                        
                        if (typeof window.TemplateJS.initDatePickers === 'function') {
                            window.TemplateJS.initDatePickers();
                        }
                    }, 200);
                }
            };
            
            script.onerror = () => {
                console.error('Ошибка загрузки скрипта, пробуем резервную копию');
                const backupScript = document.createElement('script');
                backupScript.src = 'https://cdn.jsdelivr.net/gh/tytyproject/templates@main/template-full.js?v=' + new Date().getTime();
                document.body.appendChild(backupScript);
            };
            
            document.body.appendChild(script);
        }
        
        // Настраивает обработчик формы получения шаблона
        setupAcquireFormHandler() {
            const acquireForm = document.getElementById('acquireTemplateForm');
            if (!acquireForm) return;
            
            console.log('Setting up acquire form handler');
            
            // Проверяем наличие CSRF-токена
            const csrfToken = acquireForm.querySelector('input[name="_token"]');
            if (!csrfToken) {
                console.warn('CSRF token not found in form');
                
                // Пытаемся найти токен в meta или создаем новый input
                const metaToken = document.querySelector('meta[name="csrf-token"]');
                if (metaToken) {
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = metaToken.getAttribute('content');
                    acquireForm.appendChild(tokenInput);
                    console.log('Added CSRF token from meta tag');
                }
            } else {
                console.log('CSRF token found in form');
            }
            
            // Проверяем, можно ли получить шаблон на основе серийных данных
            if (this.seriesData && this.seriesData.acquired_count >= this.seriesData.series_quantity) {
                const submitButton = acquireForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Нет доступных экземпляров';
                    console.log('Disabled submit button - all templates acquired');
                }
            }
            
            // Устанавливаем обработчик отправки формы
            acquireForm.addEventListener('submit', this.handleAcquireFormSubmit.bind(this));
        }
        
        // Обработчик отправки формы получения шаблона
        handleAcquireFormSubmit(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const statusDiv = document.getElementById('template-acquire-status');
            
            console.log('Acquire form submitted', {
                action: form.action,
                method: form.method,
                hasCSRF: !!form.querySelector('input[name="_token"]'),
                csrfValue: form.querySelector('input[name="_token"]')?.value
            });
            
            // Отключаем кнопку и показываем индикатор загрузки
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Получение...';
            }
            
            // Показываем статус
            if (statusDiv) {
                statusDiv.classList.remove('d-none');
                statusDiv.classList.remove('alert-success', 'alert-danger', 'alert-warning');
                statusDiv.classList.add('alert-info');
                statusDiv.querySelector('.message').textContent = 'Отправка запроса...';
            }
            
            // Проверяем и добавляем CSRF токен при необходимости
            if (!form.querySelector('input[name="_token"]')) {
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = this.config.csrfToken;
                form.appendChild(tokenInput);
                console.log('Added missing CSRF token to form');
            }
            
            // Отправляем запрос
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response received', {
                    status: response.status,
                    redirected: response.redirected,
                    url: response.url
                });
                
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                
                return response.text();
            })
            .then(html => {
                if (!html) return;
                
                // Анализируем ответ HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Ищем сообщения
                const successMsg = doc.querySelector('.alert-success');
                const errorMsg = doc.querySelector('.alert-danger');
                const infoMsg = doc.querySelector('.alert-info');
                
                if (successMsg) {
                    console.log('Success message found');
                    if (statusDiv) {
                        statusDiv.classList.remove('d-none', 'alert-info', 'alert-danger');
                        statusDiv.classList.add('alert-success');
                        statusDiv.querySelector('.message').textContent = successMsg.textContent.trim();
                    }
                    
                    // Перезагружаем страницу через 2 секунды
                    setTimeout(() => window.location.reload(), 2000);
                } 
                else if (errorMsg) {
                    console.log('Error message found');
                    if (statusDiv) {
                        statusDiv.classList.remove('d-none', 'alert-info', 'alert-success');
                        statusDiv.classList.add('alert-danger');
                        statusDiv.querySelector('.message').textContent = errorMsg.textContent.trim();
                    }
                    
                    // Восстанавливаем кнопку
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="bi bi-download me-2"></i> Получить шаблон';
                    }
                }
                else if (infoMsg) {
                    console.log('Info message found');
                    if (statusDiv) {
                        statusDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
                        statusDiv.classList.add('alert-warning');
                        statusDiv.querySelector('.message').textContent = infoMsg.textContent.trim();
                    }
                    
                    // Восстанавливаем кнопку
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="bi bi-download me-2"></i> Получить шаблон';
                    }
                }
                else {
                    // Если нет понятных сообщений, перезагружаем страницу
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error acquiring template:', error);
                
                if (statusDiv) {
                    statusDiv.classList.remove('d-none', 'alert-info', 'alert-success');
                    statusDiv.classList.add('alert-danger');
                    statusDiv.querySelector('.message').textContent = 'Произошла ошибка. Пожалуйста, попробуйте позже.';
                }
                
                // Восстанавливаем кнопку
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="bi bi-download me-2"></i> Повторить попытку';
                }
            });
        }
    }

    // Инициализируем просмотрщик шаблона после загрузки DOM
    document.addEventListener('DOMContentLoaded', function() {
        const viewer = new TemplateViewer();
        viewer.init();
        
        // Делаем доступным обработчик отправки формы
        window.handleFormSubmit = function(form, event) {
            console.log('Global form submit handler called');
            
            // Проверяем, является ли форма формой для получения шаблона
            if (form.id === 'acquireTemplateForm' && viewer.handleAcquireFormSubmit) {
                viewer.handleAcquireFormSubmit(event);
                return;
            }
            
            const submitButton = form.querySelector('button[type="submit"]');
            
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Обработка...';
                
                // Возвращаем кнопку в исходное состояние через 10 секунд в случае ошибки
                setTimeout(() => {
                    if (submitButton.disabled) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="bi bi-download"></i> Получить';
                    }
                }, 10000);
            }
        };
        
        // Функция для создания файла template-full.js, если его нет
        function createFullTemplateScript() {
            const scripts = document.querySelectorAll('#template-html-content script');
            let fullScriptContent = '';
            
            scripts.forEach(script => {
                if (!script.src && script.textContent) {
                    fullScriptContent += script.textContent + "\n\n";
                }
            });
            
            if (fullScriptContent) {
                // В продакшене тут можно отправить на сервер для сохранения полной версии
                console.log('Полный скрипт собран, длина:', fullScriptContent.length);
            }
        }
        
        // В режиме отладки можем собрать полный скрипт
        // setTimeout(createFullTemplateScript, 3000);
    });
</script>
