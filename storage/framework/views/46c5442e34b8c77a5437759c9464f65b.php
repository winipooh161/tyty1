<!-- Панель настроек шаблона -->
<div class="modal-panel" id="settings-offcanvas-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-panel-dialog">
        <div class="modal-panel-content">
            <div class="modal-panel-header">
                <h5 class="modal-panel-title">Настройки шаблона</h5>
                <button type="button" class="modal-panel-close" data-modal-close aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-panel-body">
                <form id="template-form" action="<?php echo e(route('client.templates.save', $template->id)); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="is_new_template" value="<?php echo e(isset($is_new_template) && $is_new_template ? 1 : 0); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Название шаблона</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo e($userTemplate->name ?? $template->name); ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <div class="mb-3">
                            <label for="cover_file" class="form-label">Обложка шаблона</label>
                            
                            <!-- Визуальный индикатор состояния файла -->
                            <div class="file-status mb-2" id="fileStatusIndicator">
                                <?php if(isset($media_editor_file) || (isset($userTemplate) && $userTemplate->cover_path)): ?>
                                    <div class="alert alert-success py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            <span>Файл обложки загружен</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            <span>Требуется загрузить обложку</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Контейнер для предпросмотра файла -->
                            <div class="file-preview-container mb-3" id="filePreviewContainer" 
                                 style="display: <?php echo e(isset($media_editor_file) || (isset($userTemplate) && $userTemplate->cover_path) ? 'block' : 'none'); ?>">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="text-center mb-2">Выбранный файл</h6>
                                        <div class="preview-content text-center">
                                            <?php if(isset($media_editor_file)): ?>
                                                <?php if($media_editor_type == 'image'): ?>
                                                    <img src="<?php echo e(asset('storage/template_covers/' . $media_editor_file)); ?>" class="img-fluid preview-img" alt="Preview">
                                                <?php else: ?>
                                                    <video src="<?php echo e(asset('storage/template_covers/' . $media_editor_file)); ?>" class="img-fluid preview-video" controls></video>
                                                <?php endif; ?>
                                                <div class="mt-1 text-muted">Файл из редактора медиа</div>
                                            <?php elseif(isset($userTemplate) && $userTemplate->cover_path): ?>
                                                <?php if($userTemplate->cover_type == 'image'): ?>
                                                    <img src="<?php echo e(asset('storage/template_covers/' . $userTemplate->cover_path)); ?>" class="img-fluid preview-img" alt="Preview">
                                                <?php else: ?>
                                                    <video src="<?php echo e(asset('storage/template_covers/' . $userTemplate->cover_path)); ?>" class="img-fluid preview-video" controls></video>
                                                <?php endif; ?>
                                                <div class="mt-1 text-muted">Текущий файл обложки</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-2 d-flex justify-content-between">
                                            <button type="button" class="btn btn-sm btn-danger" id="removeFileBtn">Удалить</button>
                                            <button type="button" class="btn btn-sm btn-primary" id="editInMediaEditorBtn">Редактировать</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Поле загрузки файла -->
                            <div class="input-group">
                                <input type="file" class="form-control" id="cover_file" name="cover_file" 
                                       accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm"
                                       <?php echo e(!isset($media_editor_file) && (!isset($userTemplate) || !$userTemplate->cover_path) ? 'required' : ''); ?>>
                                <button type="button" class="btn btn-outline-secondary" id="openMediaEditorBtn">
                                    <i class="bi bi-pencil-square"></i> В редакторе
                                </button>
                            </div>
                            <div class="form-text">Поддерживаются (JPG, PNG, GIF) и видео (MP4, WebM) до 15 секунд</div>
                            
                            <!-- Скрытые поля для передачи информации о файле -->
                            <?php if(isset($media_editor_file)): ?>
                                <input type="hidden" name="media_editor_file" id="media_editor_file" value="<?php echo e($media_editor_file); ?>">
                                <input type="hidden" name="media_editor_type" id="media_editor_type" value="<?php echo e($media_editor_type); ?>">
                                <input type="hidden" name="has_existing_cover" id="has_existing_cover" value="1">
                            <?php elseif(isset($userTemplate) && $userTemplate->cover_path): ?>
                                <input type="hidden" name="has_existing_cover" id="has_existing_cover" value="1">
                            <?php endif; ?>
                            
                            <?php $__errorArgs = ['cover_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <input type="hidden" id="html_content" name="html_content" value="<?php echo e($userTemplate->html_content ?? $template->html_content); ?>">
                        <input type="hidden" id="custom_data" name="custom_data" value="<?php echo e(isset($userTemplate) && $userTemplate->custom_data ? json_encode($userTemplate->custom_data) : '{}'); ?>">
                        
                        <!-- Настройки серии шаблонов -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_series" 
                                        <?php echo e((isset($userTemplate) && isset($userTemplate->custom_data['is_series']) && $userTemplate->custom_data['is_series']) ? 'checked' : ''); ?>>
                                    <label class="form-check-label fw-bold" for="is_series">Серия шаблонов</label>
                                </div>
                            </div>
                            <div class="card-body series-settings" style="display: <?php echo e((isset($userTemplate) && isset($userTemplate->custom_data['is_series']) && $userTemplate->custom_data['is_series']) ? 'block' : 'none'); ?>">
                                <div class="mb-3">
                                    <label for="series_quantity" class="form-label">Количество шаблонов в серии</label>
                                    <input type="number" class="form-control" id="series_quantity" min="1" max="1000" 
                                        value="<?php echo e(isset($userTemplate) && isset($userTemplate->custom_data['series_quantity']) ? $userTemplate->custom_data['series_quantity'] : 1); ?>">
                                    <div class="form-text">Укажите, сколько пользователей смогут получить этот шаблон</div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="limit_one_per_user" 
                                            <?php echo e((isset($userTemplate) && isset($userTemplate->custom_data['limit_one_per_user']) && $userTemplate->custom_data['limit_one_per_user']) ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="limit_one_per_user">
                                            Ограничить: один шаблон на пользователя
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(isset($vipUsers) && count($vipUsers) > 0): ?>
                        <div class="mb-3">
                            <label for="target_user_id" class="form-label">Назначить VIP-пользователю</label>
                            <select class="form-select" id="target_user_id" name="target_user_id">
                                <option value="">Без назначения</option>
                                <?php $__currentLoopData = $vipUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vipUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($vipUser->id); ?>" <?php echo e((isset($userTemplate) && $userTemplate->target_user_id == $vipUser->id) ? 'selected' : ''); ?>>
                                        <?php echo e($vipUser->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" id="close-settings-btn">Отмена</button>
                        <button type="submit" class="btn btn-primary" id="save-template-btn">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Устанавливаем функцию для обновления статуса файла
    function updateFileStatus() {
        const mediaEditorFile = document.getElementById('media_editor_file');
        const hasExistingCover = document.getElementById('has_existing_cover');
        const coverFile = document.getElementById('cover_file');
        const previewContainer = document.getElementById('filePreviewContainer');
        const statusIndicator = document.getElementById('fileStatusIndicator');
        
        // Проверяем наличие файла
        const hasFile = (mediaEditorFile && mediaEditorFile.value) || 
                        (hasExistingCover && hasExistingCover.value === '1') ||
                        (coverFile && coverFile.files && coverFile.files.length > 0);
        
        // Обновляем индикатор статуса
        if (statusIndicator) {
            statusIndicator.innerHTML = hasFile ? 
                `<div class="alert alert-success py-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <span>Файл обложки загружен</span>
                    </div>
                </div>` : 
                `<div class="alert alert-warning py-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span>Требуется загрузить обложку</span>
                    </div>
                </div>`;
        }
        
        // Обновляем обязательность поля загрузки
        if (coverFile) {
            if (hasFile) {
                coverFile.removeAttribute('required');
            } else {
                coverFile.setAttribute('required', 'required');
            }
        }
        
        // Обновляем видимость предпросмотра
        if (previewContainer) {
            previewContainer.style.display = hasFile ? 'block' : 'none';
        }
        
        console.log('File status updated. Has file:', hasFile);
    }
    
    // Функция для очистки файла
    function clearFile() {
        // Удаляем скрытые поля для медиа-редактора
        const mediaEditorFile = document.getElementById('media_editor_file');
        const mediaEditorType = document.getElementById('media_editor_type');
        const hasExistingCover = document.getElementById('has_existing_cover');
        
        if (mediaEditorFile) mediaEditorFile.remove();
        if (mediaEditorType) mediaEditorType.remove();
        if (hasExistingCover) hasExistingCover.remove();
        
        // Очищаем поле загрузки файла
        const coverFile = document.getElementById('cover_file');
        if (coverFile) coverFile.value = '';
        
        // Очищаем предпросмотр
        const previewContent = document.querySelector('.preview-content');
        if (previewContent) previewContent.innerHTML = '';
        
        // Обновляем статус файла
        updateFileStatus();
    }
    
    // Обработчик для кнопки удаления файла
    const removeFileBtn = document.getElementById('removeFileBtn');
    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', clearFile);
    }
    
    // Обработчик для открытия редактора медиа
    const openMediaEditorBtn = document.getElementById('openMediaEditorBtn');
    if (openMediaEditorBtn) {
        openMediaEditorBtn.addEventListener('click', function() {
            // Получаем ID шаблона
            const templateId = document.querySelector('input[name="is_new_template"]')
                ? document.querySelector('input[name="is_new_template"]').getAttribute('data-template-id')
                : '<?php echo e($template->id ?? 0); ?>';
            
            window.location.href = '/media/editor/' + templateId;
        });
    }
    
    // Обработчик для кнопки редактирования в редакторе медиа
    const editInMediaEditorBtn = document.getElementById('editInMediaEditorBtn');
    if (editInMediaEditorBtn) {
        editInMediaEditorBtn.addEventListener('click', function() {
            const templateId = '<?php echo e($template->id ?? 0); ?>';
            window.location.href = '/media/editor/' + templateId;
        });
    }
    
    // Обработчик изменения поля загрузки файла
    const coverFile = document.getElementById('cover_file');
    if (coverFile) {
        coverFile.addEventListener('change', function() {
            // При выборе нового файла удаляем информацию о ранее загруженном файле
            clearFile();
            
            // Обновляем предпросмотр
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const previewContainer = document.getElementById('filePreviewContainer');
                const previewContent = previewContainer.querySelector('.preview-content');
                
                // Создаем предпросмотр в зависимости от типа файла
                if (file.type.startsWith('image/')) {
                    previewContent.innerHTML = `
                        <img src="${URL.createObjectURL(file)}" class="img-fluid preview-img" alt="Preview">
                        <div class="mt-1 text-muted">Новый файл (не сохранен)</div>
                    `;
                } else if (file.type.startsWith('video/')) {
                    previewContent.innerHTML = `
                        <video src="${URL.createObjectURL(file)}" class="img-fluid preview-video" controls></video>
                        <div class="mt-1 text-muted">Новый файл (не сохранен)</div>
                    `;
                }
                
                // Обновляем статус файла
                updateFileStatus();
            }
        });
    }
    
    // Обработчик для переключателя "Серия шаблонов"
    const isSeriesCheckbox = document.getElementById('is_series');
    const seriesSettings = document.querySelector('.series-settings');
    
    if (isSeriesCheckbox && seriesSettings) {
        isSeriesCheckbox.addEventListener('change', function() {
            seriesSettings.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Функция для обновления custom_data перед отправкой формы
    function updateCustomData() {
        const customDataField = document.getElementById('custom_data');
        if (!customDataField) return;
        
        try {
            // Парсим текущие данные (или создаем пустой объект)
            let customData = {};
            try {
                if (customDataField.value) {
                    customData = JSON.parse(customDataField.value);
                }
            } catch (e) {
                console.error('Ошибка при разборе JSON в custom_data:', e);
                customData = {};
            }
            
            // Обновляем данные о серии
            const isSeriesCheckbox = document.getElementById('is_series');
            const seriesQuantityInput = document.getElementById('series_quantity');
            const limitOnePerUserCheckbox = document.getElementById('limit_one_per_user');
            
            if (isSeriesCheckbox) {
                customData.is_series = isSeriesCheckbox.checked;
            }
            
            if (seriesQuantityInput && isSeriesCheckbox && isSeriesCheckbox.checked) {
                const quantity = parseInt(seriesQuantityInput.value, 10);
                customData.series_quantity = isNaN(quantity) || quantity < 1 ? 1 : quantity;
            }
            
            if (limitOnePerUserCheckbox && isSeriesCheckbox && isSeriesCheckbox.checked) {
                customData.limit_one_per_user = limitOnePerUserCheckbox.checked;
            }
            
            // Обновляем поле custom_data
            customDataField.value = JSON.stringify(customData);
            console.log('Custom data обновлен:', customDataField.value);
        } catch (e) {
            console.error('Ошибка при обновлении custom_data:', e);
        }
    }
    
    // Проверяем наличие ошибок валидации и отображаем их
    if (document.querySelectorAll('.invalid-feedback').length > 0) {
        // Если есть ошибка валидации, показываем окно с настройками
        if (window.modalPanel) {
            window.modalPanel.openModal('settings-offcanvas-modal');
        }
    }
    
    // Добавляем обработчик отправки формы с дополнительной проверкой
    const templateForm = document.getElementById('template-form');
    if (templateForm) {
        templateForm.addEventListener('submit', function(e) {
            // Обновляем custom_data перед отправкой
            updateCustomData();
            
            // Обновляем html_content из текущего содержимого редактора
            const templatePreview = document.getElementById('template-preview');
            const htmlContent = document.getElementById('html_content');
            
            if (templatePreview && htmlContent) {
                htmlContent.value = templatePreview.innerHTML;
                console.log('HTML content updated before save');
            }
            
            // Дополнительная проверка на наличие файла обложки
            const mediaEditorFile = document.getElementById('media_editor_file');
            const hasExistingCover = document.getElementById('has_existing_cover');
            const coverFile = document.getElementById('cover_file');
            
            const hasFile = (mediaEditorFile && mediaEditorFile.value) || 
                            (hasExistingCover && hasExistingCover.value === '1') ||
                            (coverFile && coverFile.files && coverFile.files.length > 0);
            
            if (!hasFile) {
                e.preventDefault();
                alert('Пожалуйста, выберите файл обложки для шаблона');
                return false;
            }
            
            // Дополнительная проверка на состав HTML-контента
            const htmlContentField = document.getElementById('html_content');
            if (htmlContentField && (!htmlContentField.value || htmlContentField.value.trim().length < 10)) {
                e.preventDefault();
                alert('Содержимое шаблона некорректно. Пожалуйста, заполните шаблон перед сохранением.');
                return false;
            }
            
            document.getElementById('save-template-btn').disabled = true;
            document.getElementById('save-template-btn').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохранение...';
        });
    }
    
    // Инициализация: обновляем статус файла при загрузке страницы
    updateFileStatus();
    
    // Добавляем атрибут data-template-id для правильной передачи ID шаблона
    const isNewTemplateInput = document.querySelector('input[name="is_new_template"]');
    if (isNewTemplateInput) {
        isNewTemplateInput.setAttribute('data-template-id', '<?php echo e($template->id ?? 0); ?>');
    }
    
    // Добавляем обработчик для закрытия модального окна
    const closeSettingsBtn = document.getElementById('close-settings-btn');
    if (closeSettingsBtn) {
        closeSettingsBtn.addEventListener('click', function() {
            if (window.modalPanel) {
                window.modalPanel.closeModal();
            }
        });
    }
});
</script>

<style>
.file-preview-container .card {
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 0.25rem;
    overflow: hidden;
}

.preview-content {
    max-height: 300px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 10px;
}

.preview-img, .preview-video {
    max-height: 250px;
    max-width: 100%;
    object-fit: contain;
}

.file-status .alert {
    margin-bottom: 0;
}
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-template-settings.blade.php ENDPATH**/ ?>