<!-- Контейнер для обложки -->
<div id="coverContainer" class="cover-container">
    <?php if($userTemplate->cover_path): ?>
        <?php
            $coverPath = 'storage/template_covers/'.$userTemplate->cover_path;
            $coverExists = file_exists(public_path($coverPath));
        ?>
        
        <?php if($userTemplate->cover_type === 'video' && $coverExists): ?>
            <video id="coverVideo" class="cover-video" autoplay loop muted playsinline>
                <source src="<?php echo e(asset($coverPath)); ?>" type="video/<?php echo e(pathinfo($userTemplate->cover_path, PATHINFO_EXTENSION)); ?>">
                Ваш браузер не поддерживает видео.
            </video>
        <?php elseif($userTemplate->cover_type === 'image' && $coverExists): ?>
            <img src="<?php echo e(asset($coverPath)); ?>" class="cover-image" alt="<?php echo e($userTemplate->name); ?>">
        <?php else: ?>
            <!-- Запасное изображение или сообщение при отсутствии обложки -->
            <div class="cover-fallback">
                <div class="fallback-content">
                    <i class="bi bi-image text-white mb-2" style="font-size: 3rem;"></i>
                    <h3 class="text-white"><?php echo e($userTemplate->name); ?></h3>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Запасное изображение при отсутствии обложки -->
        <div class="cover-fallback">
            <div class="fallback-content">
                <i class="bi bi-file-earmark-text text-white mb-2" style="font-size: 3rem;"></i>
                <h3 class="text-white"><?php echo e($userTemplate->name); ?></h3>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Кнопка пропуска обложки с улучшенным интерфейсом как в редакторе -->
    <div class="skip-btn" id="toggleCoverBtn">
        <span id="skipBtnText">Пропустить</span>
        <i class="bi bi-chevron-down"></i>
    </div>
    
    <!-- Индикатор прогресса свайпа -->
    <div class="swipe-progress-container">
        <div id="swipeProgress" class="swipe-progress"></div>
    </div>
</div>

<!-- Индикатор возврата к обложке -->
<div id="returnToCover" class="return-to-cover">
    <div class="return-indicator">
        <i class="bi bi-chevron-up"></i>
        <span>Вернуться к обложке</span>
    </div>
</div>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/cover.blade.php ENDPATH**/ ?>