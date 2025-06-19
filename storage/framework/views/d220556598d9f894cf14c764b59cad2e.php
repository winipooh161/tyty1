

<?php $__env->startSection('content'); ?>
<div class="media-editor-container fullscreen-editor">
    <!-- Индикатор загрузки -->
    <?php echo $__env->make('media.media-editor.processing-indicator', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Секция загрузки файла -->
    <?php echo $__env->make('media.media-editor.upload-section', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Секция для редактирования изображений -->
    <?php echo $__env->make('media.media-editor.image-editor', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Секция для редактирования видео -->
    <?php echo $__env->make('media.media-editor.video-editor', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Действия -->
    <?php echo $__env->make('media.media-editor.action-buttons', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>

<!-- Передаем ID шаблона, если он был передан -->
<?php if(isset($template)): ?>
<input type="hidden" id="templateId" value="<?php echo e($template->id); ?>">
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('styles'); ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<?php echo $__env->make('media.media-editor.styles', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="<?php echo e(asset('js/media-editor.js')); ?>" defer></script>
<?php $__env->stopSection(); ?>
        
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/media/editor.blade.php ENDPATH**/ ?>