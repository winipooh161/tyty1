<!-- Форма редактирования шаблона -->
<form id="template-save-form" 
      action="<?php echo e(isset($userTemplate) ? route('templates.save', $userTemplate->template_id) : route('templates.save', $template->id)); ?>" 
      method="POST" 
      enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <!-- Скрытые поля для данных шаблона -->
    <input type="hidden" id="html_content" name="html_content" value="<?php echo e($userTemplate->html_content ?? $template->html_content ?? ''); ?>">
    <input type="hidden" id="custom_data" name="custom_data" value="<?php echo e($userTemplate->custom_data ?? $template->custom_data ?? '{}'); ?>">
    
    <!-- Если это новый шаблон, добавляем флаг -->
    <?php if(isset($is_new_template) && $is_new_template): ?>
        <input type="hidden" name="is_new_template" value="1">
    <?php endif; ?>
    
    <!-- Если медиа файл был загружен в сессии, добавляем эту информацию -->
    <?php if(session('media_editor_file')): ?>
        <input type="hidden" name="media_file" value="<?php echo e(session('media_editor_file')); ?>">
        <input type="hidden" name="media_type" value="<?php echo e(session('media_editor_type')); ?>">
    <?php endif; ?>
    
    <!-- Дополнительные скрытые поля для данных о серии -->
    <input type="hidden" id="is_series_template" name="is_series_template" value="0">
    <input type="hidden" id="series_quantity_value" name="series_quantity" value="1">
    <input type="hidden" id="required_scans_value" name="required_scans" value="1">
</form>

<!-- Индикатор загрузки при отправке формы -->
<div class="loading-spinner-overlay" id="form-submit-spinner">
    <div class="loading-spinner-container">
        <div class="loading-spinner">
            <img src="<?php echo e(asset('images/center-icon.svg')); ?>" class="loading-spinner-icon" alt="Loading...">
        </div>
        <p class="loading-text">Сохранение шаблона...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация формы
    const templateForm = document.getElementById('template-save-form');
    const saveButton = document.getElementById('save-template-btn');
    const formSpinner = document.getElementById('form-submit-spinner');
    
    // Функция для отображения индикатора загрузки
    function showSpinner() {
        if (formSpinner) {
            formSpinner.classList.add('show');
        }
    }
    
    // Функция для обновления данных серии из полей шаблона (улучшенная)
    function updateSeriesData() {
        try {
            // Проверяем содержимое на наличие полей серии
            const templateContent = document.getElementById('template-content');
            if (!templateContent) return;
            
            const seriesQuantityField = templateContent.querySelector('[data-editable="series_quantity"]');
            const requiredScansField = templateContent.querySelector('[data-editable="required_scans"]');
            
            // Если есть поля серии, обрабатываем их значения
            if (seriesQuantityField || requiredScansField) {
                // Получаем значения (с валидацией)
                let quantityValue = 1;
                let scansValue = 1;
                
                if (seriesQuantityField) {
                    quantityValue = seriesQuantityField.tagName === 'INPUT' ? 
                        parseInt(seriesQuantityField.value || seriesQuantityField.placeholder || '1') : 
                        parseInt(seriesQuantityField.textContent.trim() || '1');
                }
                
                if (requiredScansField) {
                    scansValue = requiredScansField.tagName === 'INPUT' ? 
                        parseInt(requiredScansField.value || requiredScansField.placeholder || '1') : 
                        parseInt(requiredScansField.textContent.trim() || '1');
                }
                
                // Убеждаемся, что значения валидны
                quantityValue = isNaN(quantityValue) || quantityValue < 1 ? 1 : quantityValue;
                scansValue = isNaN(scansValue) || scansValue < 1 ? 1 : scansValue;
                
                // Определяем, является ли шаблон серийным
                const isSeries = quantityValue > 1;
                
                // Обновляем скрытые поля формы
                document.getElementById('is_series_template').value = isSeries ? '1' : '0';
                document.getElementById('series_quantity_value').value = quantityValue.toString();
                document.getElementById('required_scans_value').value = scansValue.toString();
                
                console.log('Series data updated:', {
                    is_series: isSeries,
                    quantity: quantityValue,
                    scans: scansValue
                });
                
                // Обновляем также пользовательские данные
                updateCustomDataWithSeries(isSeries, quantityValue, scansValue);
            }
        } catch (error) {
            console.error('Ошибка при обновлении данных серии:', error);
        }
    }
    
    // Новая функция для обновления custom_data с данными о серии
    function updateCustomDataWithSeries(isSeries, quantity, requiredScans) {
        const customDataInput = document.getElementById('custom_data');
        if (!customDataInput) return;
        
        // Получаем текущие пользовательские данные
        let customData = {};
        try {
            if (customDataInput.value) {
                customData = JSON.parse(customDataInput.value);
            }
        } catch (e) {
            console.warn('Ошибка при разборе пользовательских данных:', e);
        }
        
        // Обновляем данные о серии
        customData.is_series = isSeries;
        customData.series_quantity = quantity;
        customData.required_scans = requiredScans;
        
        // Сохраняем обновленные данные обратно в поле
        customDataInput.value = JSON.stringify(customData);
        console.log('Custom data updated with series info:', customData);
    }
    
    // Функция для синхронизации полей формы с HTML-содержимым
    function updateFormFromTemplate() {
        const templateContent = document.getElementById('template-content');
        const htmlContentInput = document.getElementById('html_content');
        
        if (templateContent && htmlContentInput) {
            htmlContentInput.value = templateContent.innerHTML;
        }
        
        // Проверяем данные серии
        updateSeriesData();
        
        // Обновляем пользовательские данные из полей
        updateCustomData();
    }
    
    // Функция для обновления пользовательских данных (улучшенная)
    function updateCustomData() {
        try {
            // Обновляем данные о серии перед обновлением custom_data
            updateSeriesData();
            
            const customDataInput = document.getElementById('custom_data');
            if (!customDataInput) return;
            
            // Получаем текущие пользовательские данные
            let customData = {};
            try {
                if (customDataInput.value) {
                    customData = JSON.parse(customDataInput.value);
                }
            } catch (e) {
                console.warn('Ошибка при разборе пользовательских данных:', e);
            }
            
            // Получаем данные из редактируемых полей
            const editableData = collectEditableFieldsData();
            
            // Объединяем с новыми данными
            const updatedData = {...customData, ...editableData};
            customDataInput.value = JSON.stringify(updatedData);
            
            console.log('All custom data updated:', updatedData);
        } catch (error) {
            console.error('Ошибка при обновлении пользовательских данных:', error);
        }
    }
    
    // Функция для сбора данных из редактируемых полей
    function collectEditableFieldsData() {
        const result = {};
        const templateContent = document.getElementById('template-content');
        if (!templateContent) return result;
        
        const editableElements = templateContent.querySelectorAll('[data-editable]');
        
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
    
    // Обработчик клика по кнопке сохранения (улучшенный)
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            // Обновляем данные о серии перед отправкой
            updateSeriesData();
            
            // Обновляем форму перед отправкой
            updateFormFromTemplate();
            
            // Еще раз проверяем данные о серии
            updateCustomData();
            
            // Показываем индикатор загрузки
            showSpinner();
            
            // Отправляем форму
            templateForm.submit();
        });
    }
    
    // Добавляем обработчик изменений для полей серии в режиме реального времени
    function setupSeriesFieldsListeners() {
        const templateContent = document.getElementById('template-content');
        if (!templateContent) return;
        
        // Функция для обработки изменений
        const handleSeriesFieldChange = function() {
            updateSeriesData();
        };
        
        // Находим поля серии и добавляем обработчики
        const seriesQuantityField = templateContent.querySelector('[data-editable="series_quantity"]');
        const requiredScansField = templateContent.querySelector('[data-editable="required_scans"]');
        
        if (seriesQuantityField) {
            if (seriesQuantityField.tagName === 'INPUT') {
                seriesQuantityField.addEventListener('input', handleSeriesFieldChange);
                seriesQuantityField.addEventListener('change', handleSeriesFieldChange);
            } else {
                seriesQuantityField.addEventListener('blur', handleSeriesFieldChange);
            }
        }
        
        if (requiredScansField) {
            if (requiredScansField.tagName === 'INPUT') {
                requiredScansField.addEventListener('input', handleSeriesFieldChange);
                requiredScansField.addEventListener('change', handleSeriesFieldChange);
            } else {
                requiredScansField.addEventListener('blur', handleSeriesFieldChange);
            }
        }
    }
    
    // Инициализируем обработчики через небольшой интервал после загрузки шаблона
    setTimeout(setupSeriesFieldsListeners, 1500);
    
    // Проверяем наличие данных серии при загрузке страницы
    setTimeout(updateSeriesData, 1000);
});document.addEventListener('DOMContentLoaded', function() {
    // DOM элементы
    const coverContainer = document.getElementById('coverContainer');
    const coverVideo = document.getElementById('coverVideo');
    const skipBtn = document.getElementById('skipBtn');
    const returnToCover = document.getElementById('returnToCover');
    const swipeProgress = document.getElementById('swipeProgress');
    
    // Переменные для отслеживания состояния свайпа
    let startY = 0;
    let isDragging = false;
    let initialScrollPosition = 0;
    
    function hideCover() {
        // Приостанавливаем видео для экономии ресурсов
        if (coverVideo) {
            coverVideo.pause();
        }
        
        coverContainer.classList.add('cover-hidden');
        document.body.classList.add('return-swipe-active');
        returnToCover.style.display = 'block';
    }
    
    // Функция для показа обложки
    function showCover() {
        coverContainer.classList.remove('cover-hidden');
        returnToCover.style.display = 'none'; // Скрываем индикатор возврата
        document.body.classList.remove('return-swipe-active');
        
        // Возобновляем видео при возврате к обложке
        if (coverVideo) {
            coverVideo.play();
        }
    }
    
    // Обработчик свайпа вниз для скрытия обложки
    if (coverContainer) {
        coverContainer.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            isDragging = true;
            initialScrollPosition = 0;
        }, { passive: true });

        coverContainer.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            
            const currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;
            
            // Свайп вниз
            if (deltaY > 0) {
                const progress = Math.min(deltaY / 150, 1); // 150px для полного свайпа
                swipeProgress.style.width = `${progress * 100}%`;
                
                if (progress >= 1) {
                    hideCover();
                    isDragging = false;
                }
            }
        }, { passive: true });

        coverContainer.addEventListener('touchend', function() {
            isDragging = false;
            swipeProgress.style.width = '0%';
        }, { passive: true });
    }
    
    // Обработчик клика на кнопке Skip
    if (skipBtn) {
        skipBtn.addEventListener('click', hideCover);
    }
    
    // Обработчик свайпа вверх для возврата к обложке
    if (returnToCover) {
        returnToCover.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
            isDragging = true;
        }, { passive: true });

        returnToCover.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            
            const currentY = e.touches[0].clientY;
            const deltaY = startY - currentY;
            
            // Свайп вверх
            if (deltaY > 50) {
                showCover();
                isDragging = false;
            }
        }, { passive: true });

        returnToCover.addEventListener('touchend', function() {
            isDragging = false;
        }, { passive: true });
        
        // Добавляем обработчик клика для возврата к обложке
        returnToCover.addEventListener('click', showCover);
    }
    
    // Функция для скрытия/показа информационной панели
    window.togglePanel = function() {
        const panel = document.getElementById('infoPanel');
        const toggleBtn = document.getElementById('togglePanel');
        
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            toggleBtn.innerHTML = '<i class="bi bi-info"></i>';
        } else {
            panel.classList.add('hidden');
            toggleBtn.innerHTML = '<i class="bi bi-info"></i>';
        }
        
        // Сохраняем состояние в localStorage
        localStorage.setItem('infoPanelHidden', panel.classList.contains('hidden'));
    };
    
    // Восстанавливаем состояние панели из localStorage
    const panelState = localStorage.getItem('infoPanelHidden');
    if (panelState === 'true') {
        document.getElementById('infoPanel').classList.add('hidden');
    }
    
    // Обработка форм в шаблоне
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Если форма имеет атрибут action, то не блокируем её отправку
            if (!this.getAttribute('action')) {
                e.preventDefault();
                alert('Эта форма доступна только в режиме предпросмотра.');
            }
        });
    });
});

</script>

<style>
    /* Стили для индикатора загрузки */
    .loading-spinner-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(255, 255, 255, 0.9);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: 0.3s all ease;
    }
    
    .loading-spinner-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    
    .loading-spinner {
        position: relative;
        width: 100px;
        height: 100px;
    }
    
    .loading-spinner-icon {
        width: 100%;
        height: 100%;
        animation: spin 2s linear infinite;
    }
    
    .loading-text {
        margin-top: 20px;
        font-size: 16px;
        color: #333;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Улучшения для мобильных устройств */
    @media (max-width: 767.98px) {
        .save-btn-container {
            padding-bottom: 100px; /* Добавляем отступ для мобильной навигации */
        }
        
        .editor-options {
            margin-top: 1rem !important;
        }
    }
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/components/editor-form.blade.php ENDPATH**/ ?>