<nav class="mb-navigation mb-dock hide-desktop">
    <!-- Добавляем безопасную зону для защиты от системных жестов -->
    <div class="mb-gesture-protection"></div>
    
    <div class="mb-fixed-container">
        <div class="mb-scroller" id="nav-scroll-container">
            <div class="mb-icons-container" id="nav-icons-container">
                <?php if(request()->is('templates/create-new/*') || request()->is('client/templates/create-new/*') || request()->is('templates/editor*') || request()->is('client/templates/editor*')): ?>
                    <!-- Иконки для страницы создания шаблонов -->
                    <div class="mb-icon-wrapper" data-icon-id="back">
                        <a href="<?php echo e(route('templates.categories')); ?>" class="mb-nav-link">
                            <div class="mb-nav-icon-wrap">
                                <img src="<?php echo e(asset('images/icons/arrow-left.svg')); ?>" class="mb-nav-icon" alt="Назад" draggable="false">
                            </div>
                        </a>
                    </div>
        
            <button type="button" id="save-template-btn" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Сохранить 
            </button>
                    <div class="mb-icon-wrapper" data-icon-id="home">
                        <a href="<?php echo e(route('home')); ?>" class="mb-nav-link <?php echo e(request()->routeIs('home') ? 'mb-active' : ''); ?>">
                            <div class="mb-nav-icon-wrap">
                                <img src="<?php echo e(asset('images/center-icon.svg')); ?>" class="mb-nav-icon" alt="Главная" draggable="false">
                            </div>
                        </a>
                    </div>
                <?php elseif(request()->is('media/editor*')): ?>
                    <!-- Иконки для страницы медиа редактора -->
                    <div class="mb-icon-wrapper" data-icon-id="back">
                        <a href="<?php echo e(url()->previous()); ?>" class="mb-nav-link">
                            <div class="mb-nav-icon-wrap">
                                <img src="<?php echo e(asset('images/icons/arrow-left.svg')); ?>" class="mb-nav-icon" alt="Назад" draggable="false">
                            </div>
                        </a>
                    </div>
                    <div class="mb-icon-wrapper" data-icon-id="save">
                        <a href="#" class="mb-nav-link" id="save-media-btn">
                            <div class="mb-nav-icon-wrap">
                                <img src="<?php echo e(asset('images/icons/save.svg')); ?>" class="mb-nav-icon" alt="Сохранить" draggable="false">
                            </div>
                        </a>
                    </div>
                <?php else: ?>
                     
                    <div class="mb-icon-wrapper" data-icon-id="qr-scanner" data-modal="true" data-modal-target="qrScannerModal">
                        <a href="#" class="mb-nav-link">
                            <div class="mb-nav-icon-wrap">
                                <img src="<?php echo e(asset('images/icons/qr-code.svg')); ?>" class="mb-nav-icon" alt="QR-сканер" draggable="false">
                            </div>
                        </a>
                    </div>
                    <div class="mb-icon-wrapper" data-icon-id="home">
                        <a href="<?php echo e(route('home')); ?>" class="mb-nav-link <?php echo e(request()->routeIs('home') ? 'mb-active' : ''); ?>">
                            <div class="mb-nav-icon-wrap">
                                <img src="<?php echo e(asset('images/center-icon.svg')); ?>" class="mb-nav-icon" alt="Главная" draggable="false">
                            </div>
                        </a>
                    </div>
                    
                    <div class="mb-icon-wrapper" data-icon-id="profile">
                        <a href="<?php echo e(route('user.templates')); ?>" class="mb-nav-link <?php echo e(request()->routeIs('user.templates') ? 'mb-active' : ''); ?>">
                            <div class="mb-nav-icon-wrap">
                                <img src="<?php echo e(asset('images/icons/person.svg')); ?>" class="mb-nav-icon" alt="Профиль" draggable="false">
                            </div>
                        </a>
                    </div>
                    
                    <div class="mb-icon-wrapper" data-icon-id="create">
                        <a href="<?php echo e(route('media.editor')); ?>" class="mb-nav-link <?php echo e(request()->routeIs('media.editor') ? 'mb-active' : ''); ?>">
                            <div class="mb-nav-icon-wrap">
                                <img src="<?php echo e(asset('images/icons/save.svg')); ?>" class="mb-nav-icon" alt="Создать" draggable="false">
                            </div>
                        </a>
                    </div>
                  
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Скрытый элемент для совместимости с существующим кодом -->
<div style="display: none;">
    <div class="action-buttons position-fixed bottom-0 start-0 end-0 p-3 bg-white shadow-lg" id="actionButtons">
        <div class="row">
            <div class="col-12">
                <button type="button" class="btn btn-success btn-lg w-100" id="saveBtn">
                    <i class="bi bi-check-lg me-2"></i>Готово
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Загрузка общих обработчиков -->
<script src="<?php echo e(asset('js/mobile-nav-handlers.js')); ?>"></script>

<script>
// Дополнительная инициализация для мобильной навигации
document.addEventListener('DOMContentLoaded', function() {
    // Принудительно показываем мобильную навигацию
    const mbNavigation = document.querySelector('.mb-navigation');
    if (mbNavigation) {
        // Применяем стили напрямую
        mbNavigation.classList.add('mb-nav-loaded');
        mbNavigation.classList.remove('mb-nav-hidden');
        mbNavigation.style.display = 'flex';
        mbNavigation.style.opacity = '1';
        mbNavigation.style.transform = 'translateY(0)';
    }
    
    // Единая функция для сохранения шаблона и формы
    function saveTemplateHandler() {
        if (typeof window.saveTemplateForm === 'function') {
            try {
                const result = window.saveTemplateForm();
                if (!result) {
                    directSubmitForm();
                }
            } catch (error) {
                console.error('Ошибка при вызове saveTemplateForm:', error);
                directSubmitForm();
            }
        } else {
            directSubmitForm();
        }
    }
    
    // Функция для прямой отправки формы
    function directSubmitForm() {
        const templateForm = document.getElementById('template-save-form');
        if (!templateForm) {
            showErrorMessage('Форма сохранения не найдена на странице');
            return false;
        }
        
        try {
            // Убедимся, что метод формы установлен правильно
            templateForm.method = 'POST';
            
            // Находим необходимые элементы
            const templateContent = document.getElementById('template-content');
            const htmlContentInput = document.getElementById('html_content');
            const customDataInput = document.getElementById('custom_data');
            
            // Заполняем HTML контент
            if (templateContent && htmlContentInput) {
                htmlContentInput.value = templateContent.innerHTML;
            }
            
            // Собираем данные из редактируемых полей
            const editableData = collectEditableFields();
            
            // Обновляем поле custom_data
            if (customDataInput) {
                try {
                    let customData = {};
                    try {
                        if (customDataInput.value) {
                            customData = JSON.parse(customDataInput.value);
                        }
                    } catch (e) {
                        console.warn('Ошибка парсинга custom_data, начинаем с пустого объекта');
                    }
                    
                    // Объединяем с новыми данными
                    const updatedData = {...customData, ...editableData};
                    customDataInput.value = JSON.stringify(updatedData);
                } catch (e) {
                    console.error('Ошибка обновления custom_data:', e);
                }
            }
            
            // Показываем индикатор загрузки
            showLoadingIndicator();
            
            // Отправляем форму
            templateForm.submit();
            
            return true;
        } catch (error) {
            console.error('Ошибка при прямой отправке формы:', error);
            hideLoadingIndicator();
            showErrorMessage('Не удалось сохранить шаблон: ' + error.message);
            return false;
        }
    }
    
    // Вспомогательная функция для сбора данных из редактируемых полей
    function collectEditableFields() {
        const result = {};
        const editableElements = document.querySelectorAll('[data-editable]');
        
        editableElements.forEach(element => {
            const fieldName = element.getAttribute('data-editable');
            let value;
            
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                value = element.value;
            } else if (element.tagName === 'SELECT') {
                value = element.value;
            } else {
                value = element.textContent;
            }
            
            result[fieldName] = value;
        });
        
        return result;
    }
    
    // Индикатор загрузки
    function showLoadingIndicator() {
        let loadingIndicator = document.getElementById('form-submit-indicator');
        
        if (!loadingIndicator) {
            loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'form-submit-indicator';
            loadingIndicator.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75';
            loadingIndicator.style.zIndex = '2000';
            
            loadingIndicator.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Сохранение шаблона...</p>
                </div>
            `;
            
            document.body.appendChild(loadingIndicator);
        } else {
            loadingIndicator.style.display = 'flex';
        }
    }
    
    function hideLoadingIndicator() {
        const loadingIndicator = document.getElementById('form-submit-indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }
    
    function showErrorMessage(message) {
        // Используем общую функцию для отображения уведомлений, если доступна
        if (window.mobileNavUtils && typeof window.mobileNavUtils.showToast === 'function') {
            window.mobileNavUtils.showToast(message, 'error');
            return;
        }
        
        // Запасной вариант
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger position-fixed bottom-0 start-0 end-0 m-3';
        errorDiv.style.zIndex = '2001';
        errorDiv.innerHTML = `
            <button type="button" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Close"></button>
            <h5>Ошибка</h5>
            <p>${message}</p>
        `;
        
        document.body.appendChild(errorDiv);
        
        // Автоматически скрываем через 5 секунд
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
        
        // Добавляем обработчик закрытия
        const closeBtn = errorDiv.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => errorDiv.remove());
        }
    }
    
    // Обработчик для кнопки сохранения в медиа редакторе
    const saveMediaBtn = document.getElementById('save-media-btn');
    if (saveMediaBtn && window.location.href.includes('media/editor')) {
        saveMediaBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (typeof window.processMedia === 'function') {
                try {
                    window.processMedia();
                } catch (error) {
                    console.error('Ошибка при вызове processMedia:', error);
                    
                    // Пытаемся использовать кнопку "Готово"
                    const saveBtn = document.getElementById('saveBtn');
                    if (saveBtn) {
                        saveBtn.click();
                    }
                }
            } else {
                const saveBtn = document.getElementById('saveBtn');
                if (saveBtn) {
                    saveBtn.click();
                } else {
                    showErrorMessage('Не найдена функция сохранения');
                }
            }
        });
    }
    
    // Обработчик для кнопки сохранения шаблона
    const mobileSaveTemplateBtn = document.getElementById('mobile-save-template-btn');
    if (mobileSaveTemplateBtn && (window.location.href.includes('templates/editor') || window.location.href.includes('client/templates/editor'))) {
        mobileSaveTemplateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            saveTemplateHandler();
        });
    }
    
    // Предотвращаем скрытие мобильной навигации на страницах редакторов
    if (window.location.href.includes('media/editor') || 
        window.location.href.includes('templates/editor') || 
        window.location.href.includes('client/templates/editor')) {
        const mbNavigation = document.querySelector('.mb-navigation');
        if (mbNavigation) {
            mbNavigation.style.display = 'flex';
        }
    }
    
    // Предотвращаем перетаскивание элементов навигации
    document.querySelectorAll('.mb-nav-icon, .mb-icon-wrapper').forEach(element => {
        // Отключаем контекстное меню
        element.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Предотвращаем dragstart
        element.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
    });
    
    // Предотвращаем стандартные жести браузера
    const nav = document.querySelector('.mb-navigation');
    if (nav) {
        nav.addEventListener('touchmove', function(e) {
            const touch = e.touches[0];
            const viewportHeight = window.innerHeight;
            
            if (viewportHeight - touch.clientY < 150) {
                e.preventDefault();
            }
        }, { passive: false });
    }
});
</script>

<style>
    /*
    * Prefixed by https://autoprefixer.github.io
    * PostCSS: v8.4.14,
    * Autoprefixer: v10.4.7
    * Browsers: last 4 version
    */

    /* Стили для ограничения мобильной навигации до 4 иконок */
    .mb-fixed-container {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        position: relative;
    }

    .mb-scroller {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE and Edge */
    }

    .mb-scroller::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari and Opera */
    }

    .mb-icons-container {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        -webkit-box-pack: start;
        -ms-flex-pack: start;
        justify-content: flex-start;
    }

    /* Фиксированная ширина для иконок, чтобы их было ровно 4 */
    .mb-icon-wrapper {
        -webkit-box-flex: 0;
        -ms-flex: 0 0 25%;
        flex: 0 0 25%;
        /* Ровно 4 элемента в ряд */
        max-width: 25%;
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
        text-align: center;
    }

    /* Состояние после завершения инициализации */
    .mb-centering-complete .mb-icons-container {
        width: auto;
        min-width: 100%;
    }

    /* Улучшенный стиль для центрального элемента */
    .mb-icon-wrapper.mb-centered {
        position: relative;
    }

    .mb-icon-wrapper.mb-centered::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        -webkit-transform: translateX(-50%);
        -ms-transform: translateX(-50%);
        transform: translateX(-50%);
        width: 8px;
        height: 2px;
        background-color: #007bff;
        border-radius: 2px;
    }

    /* Добавляем стили для защиты от системных жестов */
    .mb-gesture-protection {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 20px;
        background: transparent;
        z-index: -1;
        touch-action: none;
        pointer-events: none;
    }
    
    /* Предотвращение системных жестов при активации */
    #gestureShield.active {
        background: rgba(255,255,255,0.01);
        height: 30px;
        touch-action: none;
    }
    
    /* Предотвращение перетаскивания элементов в навигации */
    .mb-icon-wrapper {
        -webkit-touch-callout: none;
        -webkit-user-drag: none;
    }
    
    /* Дополнительные стили для предотвращения перетаскивания иконок */
    .mb-nav-icon {
        -webkit-user-drag: none;
        -khtml-user-drag: none;
        -moz-user-drag: none;
        -o-user-drag: none;
        user-drag: none;
    }
    
    /* Стиль для активной иконки, являющейся источником модального окна */
    .mb-icon-wrapper.modal-source-active {
        position: relative;
        transform: scale(0.95);
        opacity: 0.8;
    }
    
    .mb-icon-wrapper.modal-source-active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 3px;
        background-color: #007bff;
        border-radius: 2px;
    }
    
    /* Дополнительные стили для гарантии отображения мобильной навигации */
    .mb-navigation {
        display: flex !important;
        opacity: 1 !important;
        transform: translateY(0) !important;
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 999999999 !important;
    }
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/mobile-nav.blade.php ENDPATH**/ ?>