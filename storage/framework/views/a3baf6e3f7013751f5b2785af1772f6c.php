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
    
    <!-- Кнопки действий (получить/отказ) встроены в контент -->
    <div class="template-actions-container">
        <?php if(auth()->guard()->check()): ?>
            <?php
                $alreadyAcquired = \App\Models\AcquiredTemplate::where('user_id', Auth::id())
                    ->where('user_template_id', $userTemplate->id)
                    ->exists();
                    
                $isOwner = $userTemplate->user_id == Auth::id();
                
                // Проверяем, является ли шаблон серией
                $customData = is_array($userTemplate->custom_data) 
                    ? $userTemplate->custom_data 
                    : (json_decode($userTemplate->custom_data, true) ?: []);
                    
                $isSeries = isset($customData['is_series']) && $customData['is_series'];
                
                $acquiredCount = \App\Models\AcquiredTemplate::where('user_template_id', $userTemplate->id)->count();
                
                // Для серий используем указанное количество, для обычных - максимум 1
                $totalCount = $isSeries ? ($customData['series_quantity'] ?? 1) : 1;
                $isAvailable = $acquiredCount < $totalCount;
            ?>
            
            <?php if(!$alreadyAcquired && !$isOwner && $isAvailable): ?>
                <div class="certificate-buttons" id="certificate-action-buttons">
                    <form action="<?php echo e(route('series.acquire', $userTemplate->id)); ?>" method="POST" 
                          style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; background: #fff; padding: 10px;"
                          onsubmit="handleFormSubmit(this, event)">
                        <?php echo csrf_field(); ?>
                        
                        <!-- Добавляем скрытые поля для отладки -->
                        <input type="hidden" name="debug_user_id" value="<?php echo e(Auth::id()); ?>">
                        <input type="hidden" name="debug_template_id" value="<?php echo e($userTemplate->id); ?>">
                        <input type="hidden" name="debug_timestamp" value="<?php echo e(time()); ?>">
                        
                        <a href="<?php echo e(route('home')); ?>" class="acquire-template-btn red">
                            <i class="bi bi-box-arrow-in-right"></i> Отказ
                        </a>
                        <button type="submit" class="acquire-template-btn green" 
                                style="border: none; cursor: pointer;">
                            <i class="bi bi-download"></i> Получить 
                        </button>
                    </form>
                </div>
            <?php elseif($alreadyAcquired): ?>
                <div class="certificate-buttons" id="certificate-action-buttons">
                    <div style="width: 100%; text-align: center; padding: 20px; background: #f8f9fa;">
                        <p class="text-success">
                            <i class="bi bi-check-circle"></i> Вы уже получили этот шаблон
                        </p>
                        <a href="<?php echo e(route('home')); ?>" class="btn btn-primary mt-2">
                            Перейти в полученные шаблоны
                        </a>
                    </div>
                </div>
            <?php elseif($isOwner): ?>
                <div class="certificate-buttons" id="certificate-action-buttons">
                    <div style="width: 100%; text-align: center; padding: 20px; background: #f8f9fa;">
                        <p class="text-info">
                            <i class="bi bi-person-circle"></i> Это ваш шаблон
                        </p>
                        <a href="<?php echo e(route('user.templates')); ?>" class="btn btn-primary mt-2">
                            Управление шаблонами
                        </a>
                    </div>
                </div>
            <?php elseif(!$isAvailable): ?>
                <div class="certificate-buttons" id="certificate-action-buttons">
                    <div style="width: 100%; text-align: center; padding: 20px; background: #f8f9fa;">
                        <p class="text-warning">
                            <i class="bi bi-exclamation-triangle"></i> Шаблон больше не доступен
                        </p>
                        <small class="text-muted">Получено: <?php echo e($acquiredCount); ?> из <?php echo e($totalCount); ?></small>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="certificate-buttons" id="certificate-action-buttons">
                <div style="width: 100%; text-align: center; padding: 20px; background: #f8f9fa;">
                    <a href="<?php echo e(route('login')); ?>" class="acquire-template-btn">
                        <i class="bi bi-box-arrow-in-right"></i> Войти для получения
                    </a>
                </div>
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
    
    // Отображаем контент с небольшой задержкой
    setTimeout(() => {
        if (templateLoading) templateLoading.style.display = 'none';
        if (templateHtmlContent) templateHtmlContent.style.display = 'block';
        
        // Выполняем скрипты в шаблоне
        executeScriptsInTemplate();
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
            
            // Проверяем наличие и инициализацию TemplateJS
            if (window.TemplateJS && typeof window.TemplateJS.init === 'function') {
                console.log('TemplateJS found, initializing...');
                try {
                    if (!window.TemplateJS.initialized) {
                        window.TemplateJS.init({
                            debug: true,
                            mode: window.location.href.includes('/template/') ? 'view' : 'edit'
                        });
                        window.TemplateJS.initialized = true;
                    }
                } catch (error) {
                    console.error('Error initializing TemplateJS:', error);
                }
            }
            
            // Если есть SeriesTemplateHandler, инициализируем его
            if (window.SeriesTemplateHandler && typeof window.SeriesTemplateHandler.init === 'function') {
                window.SeriesTemplateHandler.init();
            }
        }, 200);
    }
});

function handleFormSubmit(form, event) {
    console.log('Form submit started', {
        formData: new FormData(form)
    });
    
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Отключаем кнопку и показываем индикатор загрузки
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Получение...';
    
    // Добавляем обработчик для отслеживания отправки формы
    form.addEventListener('submit', function() {
        console.log('Form actually submitted');
    });
    
    // Если есть ошибка, возвращаем кнопку в исходное состояние через 10 секунд
    setTimeout(() => {
        if (submitButton.disabled) {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Получить';
            console.log('Reset button after timeout');
        }
    }, 10000);
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
</style>
       <?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/template-content.blade.php ENDPATH**/ ?>