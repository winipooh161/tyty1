<?php if(isset($userTemplate->custom_data) && isset($userTemplate->custom_data['is_series']) && $userTemplate->custom_data['is_series']): ?>
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
    
<?php endif; ?>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/template-series-badge.blade.php ENDPATH**/ ?>