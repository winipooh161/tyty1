<div id="coverPreviewContainer" class="cover-container">
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
            <div class="cover-fallback">
                <div class="fallback-content">
                    <i class="bi bi-image text-white mb-2" style="font-size: 3rem;"></i>
                    <h3 class="text-white"><?php echo e($userTemplate->name); ?></h3>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="cover-fallback">
            <div class="fallback-content">
                <i class="bi bi-file-earmark-text text-white mb-2" style="font-size: 3rem;"></i>
                <h3 class="text-white"><?php echo e($userTemplate->name); ?></h3>
            </div>
        </div>
    <?php endif; ?>
</div>
  <?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/template-cover.blade.php ENDPATH**/ ?>