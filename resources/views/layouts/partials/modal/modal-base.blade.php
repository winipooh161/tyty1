<!-- Базовая структура для модальной системы -->
<div class="modal-panel-container">
    <!-- Затемнение фона для модальных окон -->
    <div class="modal-backdrop" id="modal-backdrop"></div>
</div>

<!-- Стили для модальной системы -->
<style>
/* Базовые стили модальной системы */
.modal-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1050;
    display: none;
    overflow: hidden;
    outline: 0;
}

.modal-panel-dialog {
    position: relative;
    width: 100%;
    max-width: 500px;
    margin: 1.75rem auto;
    pointer-events: none;
}

.modal-panel-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    pointer-events: auto;
    background-color: #fff;
    background-clip: padding-box;
    border-radius: 0.5rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
    outline: 0;
}

.modal-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid #eaedf2;
}

.modal-panel-title {
    margin: 0;
    line-height: 1.5;
    font-size: 1.25rem;
    font-weight: 500;
}

.modal-panel-close {
    background: transparent;
    border: none;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.15s;
    padding: 0;
    margin: 0;
}

.modal-panel-close:hover {
    opacity: 1;
}

.modal-panel-body {
    position: relative;
    flex: 1 1 auto;
    padding: 1.25rem;
    overflow-y: auto;
}

.modal-panel-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 1rem;
    border-top: 1px solid #eaedf2;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1040;
    background-color: rgba(0, 0, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s linear, visibility 0.15s linear;
}

.modal-backdrop.show {
    opacity: 1;
    visibility: visible;
}

/* Анимации для модальных окон */
.modal-panel.animate-in {
    animation: modalFadeIn 0.3s forwards;
}

.modal-panel.animate-out {
    animation: modalFadeOut 0.3s forwards;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes modalFadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(20px);
    }
}

/* Модификаторы и адаптивные стили */
.modal-panel-dialog.modal-lg {
    max-width: 800px;
}

.modal-panel-dialog.modal-sm {
    max-width: 300px;
}

.modal-panel-dialog.modal-fullscreen {
    max-width: 100%;
    height: 100%;
    margin: 0;
}

.modal-panel-dialog.modal-fullscreen .modal-panel-content {
    height: 100%;
    border-radius: 0;
}

/* Адаптивные стили для мобильных устройств */
@media (max-width: 576px) {
    .modal-panel-dialog {
        margin: 0.5rem;
    }
    
    .modal-panel-body {
        padding: 1rem;
    }
    
    .modal-panel-header, .modal-panel-footer {
        padding: 0.75rem;
    }
}
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
