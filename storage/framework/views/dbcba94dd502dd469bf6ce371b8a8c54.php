<!-- Мобильная панель инструментов для управления шаблоном -->
<div class="mobile-editor-controls d-md-none">
    <div class="mobile-toolbar">
        <button id="mobile-save-btn" class="mobile-toolbar-btn mobile-save-btn">
            <i class="bi bi-check-lg"></i>
            <span>Сохранить</span>
        </button>
        
        <button id="mobile-toggle-cover-btn" class="mobile-toolbar-btn">
            <i class="bi bi-image"></i>
            <span>Обложка</span>
        </button>
        
        <button id="mobile-settings-btn" class="mobile-toolbar-btn" data-bs-toggle="modal" data-bs-target="#templateSettingsModal">
            <i class="bi bi-sliders"></i>
            <span>Настройки</span>
        </button>
    </div>
</div>

<style>
/* Стили для мобильной панели инструментов */
.mobile-editor-controls {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    background-color: white;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.mobile-toolbar {
    display: flex;
    justify-content: space-around;
    padding: 10px 0;
}

.mobile-toolbar-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: none;
    border: none;
    outline: none;
    padding: 8px;
    color: #6c757d;
    font-size: 12px;
    cursor: pointer;
}

.mobile-toolbar-btn i {
    font-size: 20px;
    margin-bottom: 4px;
}

.mobile-save-btn {
    color: #28a745;
}

/* Анимация при нажатии */
.mobile-toolbar-btn:active {
    transform: scale(0.95);
}

/* Делаем отступ внизу контента для мобильной панели */
@media (max-width: 767.98px) {
    .editor-container {
        padding-bottom: 70px;
    }
    
    .save-btn-container {
        padding-bottom: 100px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Мобильная кнопка сохранения
    const mobileSaveBtn = document.getElementById('mobile-save-btn');
    const mainSaveBtn = document.getElementById('save-template-btn');
    
    if (mobileSaveBtn && mainSaveBtn) {
        mobileSaveBtn.addEventListener('click', function() {
            mainSaveBtn.click();
        });
    }
    
    // Мобильная кнопка переключения обложки
    const mobileToggleCoverBtn = document.getElementById('mobile-toggle-cover-btn');
    const toggleCoverBtn = document.getElementById('toggleCoverBtn');
    
    if (mobileToggleCoverBtn && toggleCoverBtn) {
        mobileToggleCoverBtn.addEventListener('click', function() {
            toggleCoverBtn.click();
        });
    }
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/components/editor-mobile-controls.blade.php ENDPATH**/ ?>