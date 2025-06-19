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
</body>
</html>



<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/template.blade.php ENDPATH**/ ?>