<?php if(isset($userTemplate->custom_data) && (
    (isset($userTemplate->custom_data['is_series']) && $userTemplate->custom_data['is_series']) ||
    (isset($userTemplate->custom_data['series_quantity']) && $userTemplate->custom_data['series_quantity'] > 1)
)): ?>
    <?php
        // Преобразуем custom_data в массив, если это строка или объект
        $customData = $userTemplate->custom_data;
        if (!is_array($customData)) {
            $customData = json_decode(json_encode($customData), true);
        }
        
        $acquiredCount = \App\Models\AcquiredTemplate::where('user_template_id', $userTemplate->id)->count();
        $totalCount = $customData['series_quantity'] ?? 0;
        $remainingCount = max(0, $totalCount - $acquiredCount);
        
        // Добавляем переменные для прогресса сканирований
        $scanCount = \App\Models\AcquiredTemplate::where('user_template_id', $userTemplate->id)
            ->where('status', 'used')
            ->count();
        $requiredScans = $customData['required_scans'] ?? 1;
    ?>
    <div class="series-badge">
        <i class="bi bi-collection me-1"></i> Серия: <?php echo e($remainingCount); ?> из <?php echo e($totalCount); ?> доступно
        <?php if($requiredScans > 1): ?>
            <span class="badge bg-info ms-2">Сканирований: <?php echo e($scanCount); ?>/<?php echo e($requiredScans); ?></span>
        <?php endif; ?>
    </div>
    
    <!-- Скрипт для инициализации данных о серии -->
    <script>
        // Делаем данные о серии доступными для JavaScript
        const seriesDataFromServer = {
            is_series: true,
            series_quantity: <?php echo e($totalCount); ?>,
            acquired_count: <?php echo e($acquiredCount); ?>,
            scan_count: <?php echo e($scanCount); ?>,
            required_scans: <?php echo e($requiredScans); ?>,
            remaining_count: <?php echo e($remainingCount); ?>

        };
    </script>
<?php endif; ?>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/template-series-badge.blade.php ENDPATH**/ ?>