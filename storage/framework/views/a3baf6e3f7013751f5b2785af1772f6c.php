<div id="templateContent" class="template-content">
    <!-- Подключаем информацию о серии -->
    <?php echo $__env->make('public.partials.template-series-badge', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    
    <!-- Индикатор загрузки -->
    <div id="template-loading" class="template-loading text-center p-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Загрузка...</p>
    </div>
    
    <!-- HTML содержимое шаблона с отложенной загрузкой -->
    <div id="template-html-content" style="display: none;">
        <?php echo $userTemplate->html_content; ?>

    </div>
    
    <!-- Индикатор для отслеживания обрезанных скриптов -->
    <div id="script-status" style="display:none;"></div>
    
    <!-- Кнопки действий (получить/отказ) встроены в контент -->
    <div class="template-actions-container">
        <?php if(auth()->guard()->check()): ?>
            <?php
                $isAvailable = isset($seriesData) ? ($seriesData['acquired_count'] < $seriesData['series_quantity']) : true;
                $alreadyAcquired = isset($acquiredTemplate);
                $isOwner = $userTemplate->user_id === Auth::id();
            ?>
            
            <?php if(!$alreadyAcquired && !$isOwner && $isAvailable): ?>
                <div id="template-acquire-status" class="alert alert-info d-none">
                    <span class="message"></span>
                </div>
                
                <form id="acquireTemplateForm" action="<?php echo e(route('series.acquire', $userTemplate->id)); ?>" method="POST" class="mt-4" onsubmit="handleFormSubmit(this, event)">
                    <?php echo csrf_field(); ?>
                    <!-- Отладочные поля -->
                    <input type="hidden" name="debug_user_id" value="<?php echo e(Auth::id()); ?>">
                    <input type="hidden" name="debug_template_id" value="<?php echo e($userTemplate->id); ?>">
                    <input type="hidden" name="debug_timestamp" value="<?php echo e(time()); ?>">
                    <button type="submit" id="acquireButton" class="btn btn-primary btn-lg px-4 py-2 d-block mx-auto">
                        <i class="bi bi-download me-2"></i> Получить шаблон
                    </button>
                </form>
            <?php elseif($alreadyAcquired): ?>
                <div class="alert alert-success mt-4">
                    <i class="bi bi-check-circle-fill me-2"></i> Вы уже получили этот шаблон
                </div>
            <?php elseif($isOwner): ?>
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle-fill me-2"></i> Вы являетесь владельцем этого шаблона
                </div>
            <?php elseif(!$isAvailable): ?>
                <div class="alert alert-warning mt-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Все доступные экземпляры уже разобраны
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                <i class="bi bi-info-circle me-2"></i> Для получения шаблона необходимо <a href="<?php echo e(route('login')); ?>">войти</a> или <a href="<?php echo e(route('register')); ?>">зарегистрироваться</a>.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Предзагрузим необходимые библиотеки -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/ru.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
// Функция для отображения HTML контента с задержкой и выполнения скриптов
document.addEventListener('DOMContentLoaded', function() {
    const templateLoading = document.getElementById('template-loading');
    const templateHtmlContent = document.getElementById('template-html-content');
    
    // Регистрируем функцию initFaqAccordion в глобальной области видимости
    window.initFaqAccordion = function() {
        console.log('Global initFaqAccordion called');
        
        const faqQuestions = document.querySelectorAll('.faq-question');
        console.log(`Found ${faqQuestions.length} FAQ questions`);
        
        faqQuestions.forEach(function (question, index) {
            question.addEventListener('click', function() {
                const faqItem = this.closest('.faq-item');
                const isActive = faqItem.classList.contains('active');
                
                // Закрываем все элементы
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                    const answer = item.querySelector('.faq-answer');
                    if (answer) answer.style.maxHeight = '0';
                });
                
                // Если элемент не был активен, открываем его
                if (!isActive) {
                    faqItem.classList.add('active');
                    const answer = faqItem.querySelector('.faq-answer');
                    if (answer) answer.style.maxHeight = answer.scrollHeight + 'px';
                }
            });
        });
        
        // Ищем и инициализируем flatpickr, если он используется
        initFlatpickr();
        
        console.log('FAQ accordion initialization complete');
    };
    
    // Функция для обнаружения обрезанных скриптов
    function checkForTruncatedScripts() {
        if (!templateHtmlContent) return false;
        
        const scripts = templateHtmlContent.querySelectorAll('script');
        let truncated = false;
        
        scripts.forEach(script => {
            const content = script.innerHTML || '';
            if (
                content.includes('addEven…') || 
                content.endsWith('addEven') || 
                content.includes('…') || 
                (content.includes('TemplateJS') && content.length < 1000) ||
                (content.includes('function() {') && !content.includes('function() { }'))
            ) {
                truncated = true;
                console.warn('Обнаружен обрезанный скрипт:', content.substring(0, 100) + '...');
            }
        });
        
        return truncated;
    }
    
    // Функция для инициализации flatpickr
    function initFlatpickr() {
        // Проверяем, загружен ли flatpickr
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
            console.log('Flatpickr successfully initialized for date fields');
        } else {
            console.warn('Flatpickr not loaded, date pickers won\'t work');
        }
    }
    
    // Функция загрузки полных версий скриптов, если они обрезаны
    function loadFullScripts() {
        if (checkForTruncatedScripts()) {
            console.log('Обнаружены обрезанные скрипты, загружаем полную версию из внешнего источника');
            
            // Добавляем полный скрипт
            const fullScript = document.createElement('script');
            fullScript.src = '/js/template-full.js?v=' + new Date().getTime();
            fullScript.onload = function() {
                console.log('Полная версия скрипта загружена');
                
                // Инициализируем TemplateJS и другие компоненты
                if (window.TemplateJS && typeof window.TemplateJS.init === 'function') {
                    window.TemplateJS.init({
                        debug: true,
                        mode: 'view'
                    });
                    console.log('TemplateJS инициализирован из полной версии');
                }
                
                // Вызываем инициализацию FAQ и других элементов
                if (typeof window.initFaqAccordion === 'function') {
                    window.initFaqAccordion();
                }
            };
            
            fullScript.onerror = function() {
                console.error('Не удалось загрузить полную версию скрипта');
                
                // Запасной вариант с CDN
                const backupScript = document.createElement('script');
                backupScript.src = 'https://cdn.jsdelivr.net/gh/tytyproject/templates@main/template-full.js?v=' + new Date().getTime();
                document.body.appendChild(backupScript);
            };
            
            document.body.appendChild(fullScript);
            
            return true;
        }
        return false;
    }
    
    // Отображаем контент с небольшой задержкой
    setTimeout(() => {
        if (templateLoading) templateLoading.style.display = 'none';
        if (templateHtmlContent) templateHtmlContent.style.display = 'block';
        
        // Проверяем и загружаем полные скрипты при необходимости
        if (!loadFullScripts()) {
            // Если не обнаружены обрезанные скрипты, выполняем стандартную инициализацию
            executeScriptsInTemplate();
        }
    }, 100);
    
    // Функция для выполнения скриптов внутри шаблона
    function executeScriptsInTemplate() {
        if (!templateHtmlContent) return;
        
        console.log('Executing scripts in template');
        
        // Сначала найдем все скрипты и создадим их новые копии
        const scripts = templateHtmlContent.querySelectorAll('script');
        
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            
            // Копируем все атрибуты
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            
            // Копируем содержимое скрипта
            newScript.textContent = oldScript.textContent;
            
            // Заменяем старый скрипт новым (это заставит браузер выполнить скрипт)
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
        
        // Инициализируем аккордеон с небольшой задержкой
        setTimeout(() => {
            if (typeof window.initFaqAccordion === 'function') {
                window.initFaqAccordion();
            }
            
            // Инициализируем обработчик серийных шаблонов
            if (typeof window.SeriesTemplateHandler !== 'undefined' && 
                typeof window.SeriesTemplateHandler.init === 'function') {
                window.SeriesTemplateHandler.init();
            }
            
            // Проверяем, не обрезались ли скрипты после выполнения
            setTimeout(checkForTruncatedScripts, 500);
        }, 200);
    }
});

// Улучшенная функция обработки отправки формы
function handleFormSubmit(form, event) {
    // Отменяем стандартную отправку формы
    event.preventDefault();
    
    console.log('Form submit started', {
        action: form.action,
        method: form.method,
        hasToken: !!form.querySelector('input[name="_token"]'),
        formData: new FormData(form)
    });
    
    const submitButton = form.querySelector('button[type="submit"]');
    const statusDiv = document.getElementById('template-acquire-status');
    
    // Отключаем кнопку и показываем индикатор загрузки
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Получение...';
    }
    
    // Показываем статусный блок с сообщением
    if (statusDiv) {
        statusDiv.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
        statusDiv.classList.add('alert-info');
        statusDiv.querySelector('.message').textContent = 'Отправка запроса...';
    }
    
    // Проверяем наличие CSRF-токена и добавляем его, если отсутствует
    if (!form.querySelector('input[name="_token"]')) {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = tokenMeta.getAttribute('content');
            form.appendChild(tokenInput);
            console.log('CSRF токен добавлен в форму');
        } else {
            console.error('CSRF токен не найден!');
        }
    }
    
    // Отправляем ajax-запрос вместо обычной отправки формы
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        if (response.redirected) {
            // Если сервер отправил редирект, следуем ему
            window.location.href = response.url;
            return Promise.reject('redirect');
        }
        
        return response.text();
    })
    .then(html => {
        console.log('Successfully submitted form');
        
        // Проверяем, содержит ли ответ сообщение об успехе или ошибке
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        const successMessage = tempDiv.querySelector('.alert-success');
        const errorMessage = tempDiv.querySelector('.alert-danger');
        const infoMessage = tempDiv.querySelector('.alert-info');
        
        if (successMessage) {
            if (statusDiv) {
                statusDiv.classList.remove('d-none', 'alert-info', 'alert-danger', 'alert-warning');
                statusDiv.classList.add('alert-success');
                statusDiv.querySelector('.message').textContent = successMessage.textContent;
            }
            
            // Перезагрузим страницу через 2 секунды для отображения обновленного статуса
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else if (errorMessage) {
            if (statusDiv) {
                statusDiv.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-warning');
                statusDiv.classList.add('alert-danger');
                statusDiv.querySelector('.message').textContent = errorMessage.textContent;
            }
            
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-download me-2"></i> Получить шаблон';
            }
        } else if (infoMessage) {
            if (statusDiv) {
                statusDiv.classList.remove('d-none', 'alert-info', 'alert-danger', 'alert-success');
                statusDiv.classList.add('alert-warning');
                statusDiv.querySelector('.message').textContent = infoMessage.textContent;
            }
            
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-download me-2"></i> Получить шаблон';
            }
        } else {
            // Если нет понятного сообщения, перезагружаем страницу
            window.location.reload();
        }
    })
    .catch(error => {
        if (error === 'redirect') {
            // Игнорируем ошибку редиректа, так как мы уже обработали ее
            return;
        }
        
        console.error('Error submitting form:', error);
        
        if (statusDiv) {
            statusDiv.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-warning');
            statusDiv.classList.add('alert-danger');
            statusDiv.querySelector('.message').textContent = 'Произошла ошибка при отправке запроса. Пожалуйста, попробуйте еще раз.';
        }
        
        // Восстанавливаем кнопку
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-download me-2"></i> Получить шаблон';
        }
    });
}
</script>

<style>
/* Стили для индикатора загрузки */
.template-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}

.template-loading .spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Добавляем стили для статуса получения шаблона */
#template-acquire-status {
    transition: all 0.3s ease;
    margin-bottom: 15px;
}
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/template-content.blade.php ENDPATH**/ ?>