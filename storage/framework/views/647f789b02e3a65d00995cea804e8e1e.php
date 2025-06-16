<!-- Базовая структура для модальной системы -->
<div class="modal-panel-container">
    <!-- Затемнение фона для модальных окон -->
    <div class="modal-backdrop" id="modal-backdrop"></div>
</div>

<!-- Стили для модальной системы -->
<style>

</style>

<!-- JavaScript для работы с модальными окнами -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Глобальная функция закрытия модального окна для использования в атрибутах onclick
    window.closeModalPanel = function(modalId) {
        if (window.modalPanel) {
            window.modalPanel.closeModal();
        } else {
            // Резервный вариант, если объект modalPanel не определен
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show', 'animate-in');
                modal.classList.add('animate-out');
                
                setTimeout(() => {
                    modal.classList.remove('animate-out');
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                    
                    // Убираем фон модального окна
                    const backdrop = document.getElementById('modal-backdrop');
                    if (backdrop) {
                        backdrop.classList.remove('show');
                    }
                }, 300);
            }
        }
    };
    
    // Создаем глобальный экземпляр системы модальных окон
    if (typeof ModalPanelSystem !== 'undefined') {
        window.modalPanel = new ModalPanelSystem();
        
        // Универсальная функция для открытия модальных окон
        window.openModalPanel = function(modalId) {
            if (window.modalPanel) {
                return window.modalPanel.openModal(modalId);
            }
            return false;
        };
    }
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-base.blade.php ENDPATH**/ ?>