<!-- Индикатор загрузки -->
<div class="processing-indicator text-center p-5 position-fixed top-0 start-0 end-0 bottom-0 bg-white" id="processingIndicator" style="display: none; z-index: 999;">
    <div class="position-absolute top-50 start-50 translate-middle">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Загрузка...</span>
        </div>
        
        <!-- Статус обработки -->
        <div id="processingStatus" class="mb-3">
            <p class="mb-1">Пожалуйста, подождите...</p>
            <div class="small text-muted">Обработка видео может занять некоторое время</div>
        </div>
        
        <!-- Индикатор прогресса -->
        <div class="progress mb-3" style="height: 8px; width: 200px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
        </div>
        
        <!-- Кнопка отмены (на случай если обработка затянется) -->
        <button type="button" class="btn btn-sm btn-outline-secondary mt-3" id="cancelProcessingBtn">
            <i class="bi bi-x-circle me-1"></i> Отменить
        </button>
    </div>
</div>

<script>
    // Обработчик для кнопки отмены
    document.addEventListener('DOMContentLoaded', function() {
        const cancelBtn = document.getElementById('cancelProcessingBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                // Показываем подтверждение
                if (confirm('Вы уверены, что хотите отменить обработку? Процесс может быть уже почти завершен.')) {
                    window.location.reload();
                }
            });
        }
    });
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/media/media-editor/processing-indicator.blade.php ENDPATH**/ ?>