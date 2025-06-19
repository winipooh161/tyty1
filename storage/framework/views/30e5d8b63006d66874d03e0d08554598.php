<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <?php echo $__env->make('public.partials.template-head', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    
    <!-- Предзагрузка библиотек для шаблона -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/ru.js"></script>
    
    <!-- Скрипт для серийных шаблонов -->
    <?php if(isset($seriesData) && $seriesData): ?>
    <script>
        // Глобальные данные о серии
        const seriesDataFromServer = <?php echo json_encode($seriesData, 15, 512) ?>;
    </script>
    <?php endif; ?>
    
    <!-- Обработчик обрезанных скриптов -->
    <script>
        // Функция для проверки обрезанных скриптов после загрузки страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Функция для проверки HTML-контента на наличие обрезанных скриптов
            function checkForTruncatedScripts() {
                const contentContainer = document.getElementById('template-html-content');
                if (!contentContainer) return;
                
                const scripts = contentContainer.querySelectorAll('script');
                let truncated = false;
                
                scripts.forEach(script => {
                    const content = script.textContent || script.innerHTML || '';
                    if (
                        content.includes('addEven…') || 
                        content.includes('…') ||
                        content.endsWith('addEven') ||
                        (content.includes('function(') && content.split('function(').length > content.split('}').length)
                    ) {
                        truncated = true;
                        console.warn('Обнаружен обрезанный скрипт:', content.substring(0, 100) + '...');
                    }
                });
                
                if (truncated) {
                    console.log('Обнаружены обрезанные скрипты, загружаем полную версию...');
                    loadFullTemplateScript();
                }
            }
            
            // Функция для загрузки полной версии скрипта шаблона
            function loadFullTemplateScript() {
                // Используем абсолютный путь к файлу
                const baseUrl = '<?php echo e(url("/")); ?>';
                const scriptUrl = baseUrl + '/js/template-full.js?v=' + new Date().getTime();
                
                // Проверяем, не загружен ли скрипт уже
                if (document.querySelector('script[src*="template-full.js"]')) {
                    console.log('Полная версия скрипта уже загружена');
                    return;
                }
                
                console.log('Загрузка скрипта из:', scriptUrl);
                
                const script = document.createElement('script');
                script.src = scriptUrl;
                script.onload = function() {
                    console.log('Полная версия скрипта загружена успешно');
                    
                    // Инициализируем TemplateJS после загрузки
                    setTimeout(function() {
                        if (window.TemplateJS && typeof window.TemplateJS.init === 'function') {
                            window.TemplateJS.init({
                                debug: true,
                                mode: 'view'
                            });
                        }
                    }, 200);
                };
                
                script.onerror = function(error) {
                    console.error('Ошибка загрузки полной версии скрипта:', error);
                    
                    // Пробуем загрузить с CDN как резервный вариант
                    const cdnUrl = 'https://cdn.jsdelivr.net/gh/tytyproject/templates@main/template-full.js';
                    console.log('Пробуем загрузить с CDN:', cdnUrl);
                    
                    const backupScript = document.createElement('script');
                    backupScript.src = cdnUrl + '?v=' + new Date().getTime();
                    document.body.appendChild(backupScript);
                };
                
                document.body.appendChild(script);
            }
            
            // Запускаем проверку после небольшой задержки
            setTimeout(checkForTruncatedScripts, 1000);
            // Повторная проверка для случаев асинхронной загрузки контента
            setTimeout(checkForTruncatedScripts, 2500);
        });
    </script>
</head>
<body>
    

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: 90%; max-width: 500px;">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: 90%; max-width: 500px;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($userTemplate)): ?>
        <div class="content-cover_content">
            <?php echo $__env->make('public.partials.template-cover', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php echo $__env->make('public.partials.template-content', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
        <?php echo $__env->make('public.partials.template-scripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php else: ?>
        <div class="container py-5">
            <div class="alert alert-warning">
                <h4><i class="bi bi-exclamation-triangle"></i> Шаблон не найден</h4>
                <p>Запрашиваемый шаблон не существует или был удален.</p>
                <a href="<?php echo e(route('home')); ?>" class="btn btn-primary mt-3">Вернуться на главную</a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Контейнер для хранения полных версий скриптов при необходимости -->
    <div id="template-scripts-container" style="display: none;"></div>
</body>
</html>



<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/template.blade.php ENDPATH**/ ?>