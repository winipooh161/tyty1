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
                templateId: <?php echo e($userTemplate->id); ?>,
                userId: <?php echo e(Auth::check() ? Auth::id() : 'null'); ?>,
                acquiredId: <?php echo e(isset($acquiredTemplate) && $acquiredTemplate ? $acquiredTemplate->id : 0); ?>,
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?php echo e(csrf_token()); ?>',
                baseUrl: '<?php echo e(url("/")); ?>',
                ...config
            };
            
            // Инициализируем данные серии с улучшенной обработкой null
            const seriesDataFromServer = <?php echo json_encode($seriesData ?? null, 15, 512) ?>;
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
                        const nonce = '<?php echo e(Str::random(10)); ?>';
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
            <?php if(auth()->guard()->check()): ?>
            const recipientNameFields = document.querySelectorAll('[data-editable="recipient_name"]');
            
            if (recipientNameFields.length > 0) {
                const userName = <?php echo json_encode(Auth::user()->name, 15, 512) ?>;
                
                recipientNameFields.forEach(field => {
                    field.innerHTML = userName;
                });
            }
            <?php endif; ?>
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
        
        // Выполняет скрипты внутри шаблона
        executeScriptsInTemplate(container) {
            if (!container) return;
            
            const scripts = container.querySelectorAll('script');
            
            scripts.forEach(oldScript => {
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
            }, 300);
        }
    }

    // Инициализируем просмотрщик шаблона после загрузки DOM
    document.addEventListener('DOMContentLoaded', function() {
        const viewer = new TemplateViewer();
        viewer.init();
        
        // Делаем доступным обработчик отправки формы
        window.handleFormSubmit = function(form, event) {
            console.log('Form submit started', {
                action: form.action,
                method: form.method
            });
            
            const submitButton = form.querySelector('button[type="submit"]');
            
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Получение...';
                
                // Возвращаем кнопку в исходное состояние через 10 секунд в случае ошибки
                setTimeout(() => {
                    if (submitButton.disabled) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="bi bi-download"></i> Получить';
                    }
                }, 10000);
            }
        };
    });
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/template-scripts.blade.php ENDPATH**/ ?>