<!-- Область предпросмотра шаблона (на весь экран) -->
<div id="template-preview" class="template-container">
    <div id="template-loading" class="template-loading">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Загрузка...</p>
    </div>
    <div id="template-content" style="display: none; min-height: 200px;">
        <!-- HTML контент будет загружен динамически -->
    </div>
</div>

<!-- Скрипт для инициализации данных о серии после загрузки шаблона -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, загружен ли SeriesTemplateHandler
    let scriptLoaded = false;
    
    // Функция для загрузки скрипта
    function loadScript() {
        if (scriptLoaded) return Promise.resolve();
        
        return new Promise((resolve, reject) => {
            console.log('Loading SeriesTemplateHandler script...');
            
            const script = document.createElement('script');
            script.src = '/js/public-template.js?v=' + new Date().getTime(); // Добавляем метку времени для обхода кеширования
            script.onload = function() {
                console.log('SeriesTemplateHandler script loaded');
                scriptLoaded = true;
                resolve();
            };
            script.onerror = function(e) {
                console.error('Error loading SeriesTemplateHandler script:', e);
                reject(e);
            };
            document.head.appendChild(script);
        });
    }
    
    // Функция для работы с контентом шаблона
    function initTemplateContent() {
        // Ожидаем загрузки контента шаблона
        const waitForTemplateContent = setInterval(function() {
            const templateContent = document.getElementById('template-content');
            
            if (templateContent && templateContent.style.display !== 'none') {
                clearInterval(waitForTemplateContent);
                console.log('Template content loaded, initializing handlers');
                
                // Проверяем, есть ли поля серии
                const hasSeriesFields = templateContent.querySelector('[data-editable="series_quantity"]') !== null;
                
                // Проверяем, есть ли скрипты в контенте
                const hasScripts = templateContent.querySelectorAll('script').length > 0;
                
                // Проверяем, не обрезаны ли скрипты
                const hasTruncatedScripts = Array.from(templateContent.querySelectorAll('script')).some(script => 
                    script.innerHTML.includes('addEven…') || 
                    script.innerHTML.endsWith('addEven')
                );
                
                if (hasTruncatedScripts) {
                    console.warn('Обнаружены обрезанные скрипты в шаблоне, исправляем...');
                    
                    // Получаем скрипты из window.templateScripts, если они доступны
                    if (window.templateScripts) {
                        console.log('Используем предзагруженные скрипты шаблона');
                        const scripts = templateContent.querySelectorAll('script');
                        
                        scripts.forEach((script, index) => {
                            const templateScript = window.templateScripts[index];
                            if (templateScript) {
                                script.textContent = templateScript;
                            }
                        });
                    }
                }
                
                // Если загружен SeriesTemplateHandler, используем его
                if (window.SeriesTemplateHandler) {
                    // Извлекаем и выполняем скрипты
                    if (window.SeriesTemplateHandler.extractAndRunScripts) {
                        window.SeriesTemplateHandler.extractAndRunScripts();
                    }
                    
                    // Инициализируем обработчик
                    window.SeriesTemplateHandler.init();
                    console.log('SeriesTemplateHandler initialized');
                } else {
                    console.log('SeriesTemplateHandler not available, using fallback initialization');
                    
                    // Извлекаем и выполняем скрипты вручную
                    const scripts = templateContent.querySelectorAll('script');
                    scripts.forEach((oldScript, index) => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });
                        
                        // Исправляем обрезанный контент
                        let scriptContent = oldScript.innerHTML;
                        if (scriptContent.includes('addEven…') || scriptContent.endsWith('addEven')) {
                            scriptContent = scriptContent.replace('elem.addEven…', 
                                'elem.addEventListener(\'keydown\', function(e) { if (e.key === \'Enter\') { e.preventDefault(); this.blur(); } });');
                        }
                        
                        newScript.textContent = scriptContent;
                        document.body.appendChild(newScript);
                        
                        if (oldScript.parentNode) {
                            oldScript.parentNode.removeChild(oldScript);
                        }
                    });
                    
                    // Пытаемся инициализировать TemplateJS напрямую
                    if (window.TemplateJS && typeof window.TemplateJS.init === 'function') {
                        try {
                            console.log('Initializing TemplateJS directly');
                            window.TemplateJS.init({
                                debug: true,
                                mode: window.location.href.includes('/editor') || window.location.href.includes('/create-new') ? 
                                    'edit' : 'view'
                            });
                        } catch (error) {
                            console.error('Error initializing TemplateJS:', error);
                        }
                    }
                    
                    // Инициализируем аккордеон
                    initFallbackAccordion();
                }
            }
        }, 300);
        
        // Останавливаем проверку через 10 секунд
        setTimeout(function() {
            clearInterval(waitForTemplateContent);
        }, 10000);
    }
    
    // Резервная функция для инициализации аккордеона
    function initFallbackAccordion() {
        const faqQuestions = document.querySelectorAll('.faq-question');
        
        if (faqQuestions.length > 0) {
            console.log('Fallback: initializing FAQ accordion');
            
            faqQuestions.forEach(function(question) {
                // Проверяем, был ли уже добавлен обработчик
                if (!question.hasAttribute('data-init')) {
                    question.setAttribute('data-init', 'true');
                    
                    question.addEventListener('click', function() {
                        const faqItem = this.closest('.faq-item');
                        if (!faqItem) return;
                        
                        const isActive = faqItem.classList.contains('active');
                        
                        // Закрываем все вопросы
                        document.querySelectorAll('.faq-item').forEach(q => {
                            q.classList.remove('active');
                        });
                        
                        // Если вопрос не был открыт, открываем его
                        if (!isActive) {
                            faqItem.classList.add('active');
                        }
                    });
                }
            });
            
            // Инициализация flatpickr
            if (typeof flatpickr === 'function') {
                const dateInputs = document.querySelectorAll('.issue-date-s, .issue-date-do');
                if (dateInputs.length > 0) {
                    dateInputs.forEach(input => {
                        flatpickr(input, {
                            dateFormat: "j F Y г.",
                            locale: "ru",
                            allowInput: true
                        });
                    });
                    console.log('Fallback: flatpickr initialized');
                }
            }
        }
    }
    
    // Загружаем скрипт и инициализируем контент
    loadScript()
        .then(initTemplateContent)
        .catch(function(error) {
            console.error('Failed to load SeriesTemplateHandler:', error);
            initTemplateContent(); // Используем резервный вариант
        });
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/components/editor-preview.blade.php ENDPATH**/ ?>