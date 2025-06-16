<!-- Маленькая информационная панель, которую можно скрыть -->
<div class="info-panel" id="infoPanel">
    <span><?php echo e($userTemplate->name); ?></span>
    <?php if(auth()->guard()->check()): ?>
        <a href="<?php echo e(route('client.templates.create-new', $userTemplate->template_id)); ?>" class="btn-use">Создать на основе</a>
    <?php else: ?>
        <a href="<?php echo e(route('login')); ?>" class="btn-use">Войти для использования</a>
    <?php endif; ?>
    <span class="close-panel" onclick="togglePanel()">&times;</span>
</div>

<!-- Кнопка для отображения панели -->
<div class="toggle-panel" id="togglePanel" onclick="togglePanel()">
    <i class="bi bi-info"></i>
</div>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/public/partials/info-panel.blade.php ENDPATH**/ ?>