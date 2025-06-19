

<?php $__env->startSection('content'); ?>
<div class="editor-container">
    
    <!-- Подключаем компонент обложки -->
    <?php echo $__env->make('templates.components.editor-cover', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Подключаем компонент предпросмотра шаблона -->
    <?php echo $__env->make('templates.components.editor-preview', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Подключаем компонент формы редактирования -->
    <?php echo $__env->make('templates.components.editor-form', [
        'template' => $template,
        'userTemplate' => $userTemplate ?? null,
        'is_new_template' => $is_new_template ?? false
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>

<!-- Улучшенные стили для обеспечения скролла -->
<style>
    @media (max-width: 767.98px) {
        .mobile-only-mode #app {
            height: auto !important;
            min-height: 100% !important;
        }
        
        .mobile-only-mode .content-wrapper {
            overflow: visible !important;
        }
        
        /* Принудительно включаем скролл для body */
        body {
            overflow-y: auto !important;
            height: auto !important;
            position: static !important;
        }
    }
</style>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<!-- Инициализация редактора шаблона -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем модуль редактора шаблона
    TemplateEditor.init({
        template: <?php echo json_encode($template ?? null, 15, 512) ?>,
        userTemplate: <?php echo json_encode($userTemplate ?? null, 15, 512) ?>,
        mediaFile: "<?php echo e(session('media_editor_file') ?? ''); ?>",
        mediaType: "<?php echo e(session('media_editor_type') ?? ''); ?>",
        isNewTemplate: <?php echo e(isset($is_new_template) && $is_new_template ? 'true' : 'false'); ?>,
        saveUrl: "<?php echo e(route('templates.save', $template->id)); ?>"
    });
    
    // Принудительно разблокируем скролл страницы
    function unblockPageScroll() {
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
        document.body.style.height = 'auto';
        document.body.style.top = '';
        document.body.style.width = '';
        
        // Удаляем все классы, которые могут блокировать скролл
        document.body.classList.remove('modal-scroll-blocked');
        document.body.classList.remove('popup-scroll-blocked');
        
        // Сбрасываем inline стили для html
        document.documentElement.style.overflow = '';
        
        // Проверяем content-wrapper
        const contentWrapper = document.querySelector('.content-wrapper');
        if (contentWrapper) {
            contentWrapper.style.overflow = 'visible';
        }
    }
    
    // Вызываем разблокировку сразу и с небольшой задержкой
    unblockPageScroll();
    setTimeout(unblockPageScroll, 500);
    setTimeout(unblockPageScroll, 1500);
    
    // Наблюдаем за изменениями стилей body
    const observeBodyStyles = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'style' || mutation.attributeName === 'class') {
                // Если стиль body изменился, и скролл блокируется, разблокируем его
                if (document.body.style.overflow === 'hidden' || 
                    document.body.classList.contains('modal-scroll-blocked') ||
                    document.body.classList.contains('popup-scroll-blocked')) {
                    setTimeout(unblockPageScroll, 100);
                }
            }
        });
    });
    
    // Запускаем наблюдение за изменениями стилей body
    observeBodyStyles.observe(document.body, {
        attributes: true
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/editor.blade.php ENDPATH**/ ?>