@extends('layouts.app')

@section('content')
<div class="editor-container">
    
    <!-- Добавляем предпросмотр обложки -->
    <div id="coverPreviewContainer" class="cover-container mb-3">
        @if(isset($userTemplate) && $userTemplate && $userTemplate->cover_path)
            @php
                $coverPath = 'storage/template_covers/'.$userTemplate->cover_path;
                $coverExists = file_exists(public_path($coverPath));
            @endphp
            
            @if($userTemplate->cover_type === 'video' && $coverExists)
                <video id="coverVideo" class="cover-video" autoplay loop muted playsinline>
                    <source src="{{ asset($coverPath) }}" type="video/{{ pathinfo($userTemplate->cover_path, PATHINFO_EXTENSION) }}">
                    Ваш браузер не поддерживает видео.
                </video>
            @elseif($userTemplate->cover_type === 'image' && $coverExists)
                <img src="{{ asset($coverPath) }}" class="cover-image" alt="{{ $userTemplate->name ?? 'Обложка шаблона' }}">
            @else
                <div class="cover-fallback">
                    <div class="fallback-content">
                        <i class="bi bi-image text-white mb-2" style="font-size: 3rem;"></i>
                        <h3 class="text-white">{{ $userTemplate->name ?? 'Предпросмотр обложки' }}</h3>
                    </div>
                </div>
            @endif
        @elseif(session('media_editor_file'))
            @php
                $mediaFile = session('media_editor_file');
                $mediaType = session('media_editor_type') ?? 'image';
                $coverPath = 'storage/template_covers/'.$mediaFile;
                $coverExists = file_exists(public_path($coverPath));
            @endphp
            
            @if($mediaType === 'video' && $coverExists)
                <video id="coverVideo" class="cover-video" autoplay loop muted playsinline>
                    <source src="{{ asset($coverPath) }}" type="video/{{ pathinfo($mediaFile, PATHINFO_EXTENSION) }}">
                    Ваш браузер не поддерживает видео.
                </video>
            @elseif($mediaType === 'image' && $coverExists)
                <img src="{{ asset($coverPath) }}" class="cover-image" alt="Обложка шаблона">
            @else
                <div class="cover-fallback">
                    <div class="fallback-content">
                        <i class="bi bi-file-earmark-text text-white mb-2" style="font-size: 3rem;"></i>
                        <h3 class="text-white">Обложка еще не загружена</h3>
                    </div>
                </div>
            @endif
        @else
            <div class="cover-fallback">
                <div class="fallback-content">
                    <i class="bi bi-file-earmark-text text-white mb-2" style="font-size: 3rem;"></i>
                    <h3 class="text-white">Загрузите обложку для шаблона</h3>
                </div>
            </div>
        @endif
        
        <!-- Добавляем кнопку смены обложки в центр контейнера -->
        <a href="{{ isset($template) ? route('media.editor.template', $template->id) : route('media.editor') }}" 
           class="change-cover-btn">
            <i class="bi bi-pencil-square me-1"></i>
            Сменить обложку
        </a>
        
        <!-- Кнопка для просмотра/скрытия обложки -->
        <div class="skip-btn" id="toggleCoverBtn">
            <span id="skipBtnText">Редактирование</span>
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
    
    <!-- Область предпросмотра шаблона (на весь экран) -->
    <div id="template-preview" class="template-container fullscreen">
        {!! isset($userTemplate) ? $userTemplate->html_content : $template->html_content !!}
    </div>

    <!-- Кнопка сохранения в обычном потоке -->
    <div class="save-btn-container ">
        <button type="button" id="save-template-btn" class="btn btn-success btn-lg">
            <i class="bi bi-check-circle me-2"></i>Сохранить шаблон
        </button>
    </div>

    <!-- Добавляем форму сохранения -->
    <form id="template-save-form" action="{{ route('client.templates.save', $template->id) }}" method="POST" enctype="multipart/form-data" style="display: none;">
        @csrf
        <input type="hidden" id="template-name" name="name" value="{{ $userTemplate->name ?? $template->name }}">
        <input type="hidden" id="html_content" name="html_content" value="">
        <input type="hidden" id="custom_data" name="custom_data" value="{{ isset($userTemplate) && $userTemplate->custom_data ? json_encode($userTemplate->custom_data) : '{}' }}">
        <input type="hidden" name="is_new_template" value="{{ isset($is_new_template) && $is_new_template ? '1' : '0' }}">
        <input type="hidden" name="media_editor_file" value="{{ session('media_editor_file') ?? '' }}">
        <input type="hidden" name="media_editor_type" value="{{ session('media_editor_type') ?? '' }}">
        @if(isset($userTemplate) && $userTemplate->cover_path)
            <input type="hidden" name="has_existing_cover" value="1">
        @endif
    </form>
    
    <!-- Панель настроек теперь подключается из отдельного файла -->
    <!-- Старый код был перемещен в modal-template-settings.blade.php -->
</div>

<!-- Убираем старый блок с фиксированной кнопкой -->
<!-- Добавляем стили для обложки -->
<style>
    /* Стили для контейнера обложки в режиме редактирования */
    .cover-container {
        position: relative;
        width: 100%;
        height: 70vh;
        z-index: 100;
        background-color: #000;
        transition: height 0.4s ease;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
   @media (max-width: 767.98px) {
    .content-wrapper {
        padding-top: 60px;
        padding: 0px 0 0 0 !important;
    }
}
    .cover-container.cover-hidden {
        height: 30vh;
    }
    
    .cover-video, .cover-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Добавление стилей для запасной обложки */
    .cover-fallback {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-align: center;
    }
    
    .fallback-content {
        max-width: 80%;
        padding: 20px;
    }
    
    /* Стили для новой кнопки смены обложки */
    .change-cover-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 10px 20px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 500;
        font-size: 16px;
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(3px);
        z-index: 110;
        display: flex;
        align-items: center;
        opacity: 0.7;
    }
    
    .change-cover-btn:hover {
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.05);
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
    }
    
    /* Стили для нижней кнопки переключения режимов */
    .skip-btn {
        position: absolute;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        background-color: rgba(0, 0, 0, 0.5);
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
        z-index: 101;
    }
    
    .swipe-progress-container {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .swipe-progress {
        height: 100%;
        background-color: white;
        width: 0%;
        transition: width 0.1s linear;
    }
    
    /* Стили для индикатора возврата к обложке */
    .return-to-cover {
        position: relative;
        width: 100%;
        padding: 10px 0;
        background: linear-gradient(180deg, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0) 100%);
        color: white;
        text-align: center;
        z-index: 90;
        transform: translateY(-100%);
        transition: transform 0.3s ease;
        cursor: pointer;
        display: none;
    }
    
    .return-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 14px;
    }
    
    body.return-swipe-active .return-to-cover {
        transform: translateY(0);
        display: block;
    }
    
    /* Стили для кнопки сохранения */
    .save-btn-container {
        text-align: center;
        padding: 20px 0 80px 0;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .save-btn-container .btn {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 30px;
        padding: 12px 30px;
        font-weight: 600;
        min-width: 200px;
    }
    
    .save-btn-container .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }
}
</style>

<!-- Скрипт для обложки -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const coverContainer = document.getElementById('coverPreviewContainer');
        const returnToCover = document.getElementById('returnToCover');
        const toggleCoverBtn = document.getElementById('toggleCoverBtn');
        const skipBtnText = document.getElementById('skipBtnText');
        const swipeProgress = document.getElementById('swipeProgress');
        
        let isCoverHidden = false;
        
        // Функция для переключения видимости обложки
        function toggleCover() {
            if (isCoverHidden) {
                // Показываем обложку
                coverContainer.classList.remove('cover-hidden');
                skipBtnText.textContent = 'Перейти к редактированию';
                toggleCoverBtn.querySelector('i').classList.remove('bi-chevron-up');
                toggleCoverBtn.querySelector('i').classList.add('bi-chevron-down');
            } else {
                // Скрываем обложку
                coverContainer.classList.add('cover-hidden');
                skipBtnText.textContent = 'Показать обложку';
                toggleCoverBtn.querySelector('i').classList.remove('bi-chevron-down');
                toggleCoverBtn.querySelector('i').classList.add('bi-chevron-up');
            }
            isCoverHidden = !isCoverHidden;
        }
        
        // Обработчик клика по кнопке
        if (toggleCoverBtn) {
            toggleCoverBtn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleCover();
            });
        }
        
        // Обработчик клика по индикатору возврата
        if (returnToCover) {
            returnToCover.addEventListener('click', function() {
                if (isCoverHidden) {
                    toggleCover();
                }
            });
        }
        
        // Автоматическое воспроизведение видео при загрузке
        const coverVideo = document.getElementById('coverVideo');
        if (coverVideo) {
            coverVideo.play().catch(error => {
                console.log("Автовоспроизведение видео не поддерживается");
            });
        }
    });
</script>

<!-- Сохраняем информацию о файлах для диагностики -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Выводим в консоль состояние медиа-файла из сессии при каждой загрузке страницы
    console.log('Editor page loaded, checking media file state:');
    
    // При открытии панели настроек принудительно обновляем статус файла
    const settingsButton = document.querySelector('[data-bs-target="#settings-offcanvas"]');
    if (settingsButton) {
        settingsButton.addEventListener('click', function() {
            // Таймаут для того, чтобы код выполнился после отрисовки панели
            setTimeout(function() {
                const updateFileStatusFn = window.updateFileStatus;
                if (typeof updateFileStatusFn === 'function') {
                    updateFileStatusFn();
                    console.log('File status forcibly updated');
                }
            }, 300);
        });
    }
});
</script>

<!-- Добавляем скрытое поле для custom_data, если его ещё нет -->
<input type="hidden" id="custom_data" name="custom_data" value="{{ isset($userTemplate) && $userTemplate->custom_data ? json_encode($userTemplate->custom_data) : '{}' }}">

<!-- Убеждаемся, что у формы есть поле для html_content -->
<input type="hidden" id="html_content" name="html_content" value="">

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const templatePreview = document.getElementById('template-preview');
    const templateForm = document.getElementById('template-save-form');
    const saveButton = document.getElementById('save-template-btn');
    
    // Получение пользовательских данных
    let customData = {};
    try {
        const customDataInput = document.getElementById('custom_data');
        if (customDataInput && customDataInput.value) {
            customData = JSON.parse(customDataInput.value);
        }
    } catch (e) {
        console.error('Ошибка при парсинге пользовательских данных', e);
    }
    
    // Функция для извлечения информации о серии из HTML содержимого
    function extractSeriesInfoFromHtml(html) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Извлекаем данные о выпущенных экземплярах
        const seriesQuantityInput = tempDiv.querySelector('[data-editable="series_quantity"]');
        let seriesQuantity = 1;
        
        if (seriesQuantityInput) {
            if (seriesQuantityInput.tagName === 'INPUT') {
                seriesQuantity = parseInt(seriesQuantityInput.value || seriesQuantityInput.placeholder || '1');
            } else {
                seriesQuantity = parseInt(seriesQuantityInput.textContent.trim() || '1');
            }
        }
        
        // Извлекаем данные о требуемых сканированиях
        const requiredScansInput = tempDiv.querySelector('[data-editable="required_scans"]');
        let requiredScans = 1;
        
        if (requiredScansInput) {
            if (requiredScansInput.tagName === 'INPUT') {
                requiredScans = parseInt(requiredScansInput.value || requiredScansInput.placeholder || '1');
            } else {
                requiredScans = parseInt(requiredScansInput.textContent.trim() || '1');
            }
        }
        
        // Определяем, есть ли поля серии в шаблоне (если quantity > 1 или есть сами поля)
        const hasSeries = seriesQuantity > 1 || !!seriesQuantityInput || !!requiredScansInput;
        
        console.log('Extracted series info:', {
            is_series: hasSeries,
            series_quantity: seriesQuantity,
            required_scans: requiredScans
        });
        
        return {
            is_series: hasSeries,
            series_quantity: seriesQuantity,
            required_scans: requiredScans
        };
    }
    
    // Улучшенная функция для сбора всех данных из редактируемых полей
    function collectEditableFieldsData() {
        const editableElements = templatePreview.querySelectorAll('[data-editable]');
        const collectedData = {};
        
        editableElements.forEach(element => {
            const fieldName = element.dataset.editable;
            let value;
            
            if (element.tagName === 'INPUT') {
                // Для input элементов берем value
                value = element.value;
                
                // Для числовых полей серии конвертируем в число
                if (['series_quantity', 'series_received', 'scan_count', 'required_scans'].includes(fieldName)) {
                    value = parseInt(value) || (fieldName === 'series_quantity' || fieldName === 'required_scans' ? 1 : 0);
                }
            } else {
                // Для других элементов берем innerHTML
                value = element.innerHTML;
            }
            
            collectedData[fieldName] = value;
            console.log('Collected field:', fieldName, '=', value, '(type:', typeof value, ')');
        });
        
        console.log('All collected editable fields data:', collectedData);
        return collectedData;
    }
    
    // Функция для обновления данных формы
    function updateFormData() {
        try {
            // Собираем данные из всех редактируемых полей
            const editableFieldsData = collectEditableFieldsData();
            
            // Получаем HTML содержимое
            const htmlContent = templatePreview.innerHTML;
            
            // Извлекаем информацию о серии из HTML
            const seriesInfo = extractSeriesInfoFromHtml(htmlContent);
            
            // Объединяем все данные, приоритет у собранных данных полей
            const updatedCustomData = {
                ...customData,
                ...seriesInfo,
                ...editableFieldsData // editableFieldsData имеет наивысший приоритет
            };
            
            // Дополнительная проверка и корректировка данных серии
            if (updatedCustomData.series_quantity && updatedCustomData.series_quantity > 1) {
                updatedCustomData.is_series = true;
            }
            
            // Обновляем поля формы
            document.getElementById('html_content').value = htmlContent;
            document.getElementById('custom_data').value = JSON.stringify(updatedCustomData);
            
            // Обновляем название шаблона, если есть поле с названием
            const titleField = templatePreview.querySelector('[data-editable="certificate_title"]');
            if (titleField) {
                const title = titleField.tagName === 'INPUT' ? titleField.value : titleField.textContent;
                if (title && title.trim()) {
                    document.getElementById('template-name').value = title.trim();
                }
            }
            
            console.log('Form data updated successfully:', {
                html_length: htmlContent.length,
                custom_data: updatedCustomData,
                template_name: document.getElementById('template-name').value
            });
            
            return true;
        } catch (error) {
            console.error('Error updating form data:', error);
            return false;
        }
    }
    
    // Обработчик клика по кнопке сохранения
    saveButton.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Показываем индикатор загрузки
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Сохранение...';
        
        // Обновляем данные формы
        const updateSuccess = updateFormData();
        
        if (updateSuccess) {
            // Отправляем форму
            templateForm.submit();
        } else {
            // Возвращаем кнопку в исходное состояние при ошибке
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="bi bi-check-circle me-2"></i>Сохранить шаблон';
            alert('Произошла ошибка при подготовке данных для сохранения. Попробуйте еще раз.');
        }
    });
    
    // Функция для инициализации элементов шаблона для редактирования
    function initializeEditableElements() {
        const editableElements = templatePreview.querySelectorAll('[data-editable]');
        
        editableElements.forEach(element => {
            const fieldName = element.dataset.editable;
            
            // Сохраняем исходное содержимое
            if (!element.dataset.defaultContent) {
                if (element.tagName === 'INPUT') {
                    element.dataset.defaultContent = element.value || element.placeholder;
                } else {
                    element.dataset.defaultContent = element.innerHTML;
                }
            }
            
            // Если есть пользовательские данные, устанавливаем их
            if (customData[fieldName] !== undefined) {
                if (element.tagName === 'INPUT') {
                    element.value = customData[fieldName];
                } else {
                    element.innerHTML = customData[fieldName];
                }
            }
            
            // Добавляем обработчики событий
            element.addEventListener('click', function(e) {
                e.stopPropagation();
                focusAndEnableEditing(this);
            });
            
            element.addEventListener('blur', function() {
                this.contentEditable = false;
                this.classList.remove('editing');
                
                // Обновляем данные при завершении редактирования
                const fieldName = this.dataset.editable;
                let newValue;
                
                if (this.tagName === 'INPUT') {
                    newValue = this.value;
                    // Для числовых полей серии конвертируем в число
                    if (['series_quantity', 'series_received', 'scan_count', 'required_scans'].includes(fieldName)) {
                        newValue = parseInt(newValue) || (fieldName === 'series_quantity' || fieldName === 'required_scans' ? 1 : 0);
                        this.value = newValue; // Обновляем отображаемое значение
                    }
                } else {
                    newValue = this.innerHTML;
                }
                
                customData[fieldName] = newValue;
                
                console.log('Updated field:', fieldName, 'value:', newValue, '(type:', typeof newValue, ')');
                
                // Автоматически обновляем форму при каждом изменении
                updateFormData();
            });
            
            // Обработка input события для input элементов
            if (element.tagName === 'INPUT') {
                element.addEventListener('input', function() {
                    const fieldName = this.dataset.editable;
                    let value = this.value;
                    
                    // Для числовых полей серии конвертируем в число
                    if (['series_quantity', 'series_received', 'scan_count', 'required_scans'].includes(fieldName)) {
                        value = parseInt(value) || (fieldName === 'series_quantity' || fieldName === 'required_scans' ? 1 : 0);
                    }
                    
                    customData[fieldName] = value;
                    console.log('Input updated:', fieldName, 'value:', value, '(type:', typeof value, ')');
                });
            }
            
            // Обработка нажатия клавиш
            element.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.blur();
                }
                if (e.key === 'Escape') {
                    // Отмена изменений
                    const fieldName = this.dataset.editable;
                    if (customData[fieldName] !== undefined) {
                        if (this.tagName === 'INPUT') {
                            this.value = customData[fieldName];
                        } else {
                            this.innerHTML = customData[fieldName];
                        }
                    } else {
                        if (this.tagName === 'INPUT') {
                            this.value = this.dataset.defaultContent || '';
                        } else {
                            this.innerHTML = this.dataset.defaultContent || '';
                        }
                    }
                    this.blur();
                }
            });
        });
    }
    
    // Функция для фокусировки и активации режима редактирования
    function focusAndEnableEditing(element) {
        // Снимаем выделение с других элементов
        document.querySelectorAll('[data-editable].editing').forEach(el => {
            if (el !== element) {
                el.classList.remove('editing');
                if (el.tagName !== 'INPUT') {
                    el.contentEditable = false;
                }
            }
        });
        
        // Переключаем режим редактирования
        element.classList.add('editing');
        
        if (element.tagName === 'INPUT') {
            element.focus();
            element.select();
        } else {
            element.contentEditable = true;
            element.focus();
        }
    }
    
    // Инициализируем редактирование
    initializeEditableElements();
    
    // Автосохранение каждые 30 секунд
    setInterval(function() {
        updateFormData();
        console.log('Auto-save: Form data updated');
    }, 30000);
    
    // Обновляем данные формы при загрузке страницы
    setTimeout(function() {
        updateFormData();
        console.log('Initial form data update completed');
    }, 1000);
    
    console.log('Template editor initialized successfully');
});
</script>
@endsection
