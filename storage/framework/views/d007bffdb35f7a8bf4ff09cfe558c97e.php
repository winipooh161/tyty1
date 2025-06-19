<!-- Модальное окно настроек шаблона -->
<div class="modal fade template-settings-modal" id="templateSettingsModal" tabindex="-1" aria-labelledby="templateSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateSettingsModalLabel">
                    <i class="bi bi-gear-fill me-2"></i>Настройки шаблона
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <div class="modal-body">
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" 
                                type="button" role="tab" aria-controls="general" aria-selected="true">
                            Основные
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="series-tab" data-bs-toggle="tab" data-bs-target="#series" 
                                type="button" role="tab" aria-controls="series" aria-selected="false">
                            Серия
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" 
                                type="button" role="tab" aria-controls="advanced" aria-selected="false">
                            Дополнительно
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="settingsTabsContent">
                    <!-- Вкладка основных настроек -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="mb-3">
                            <label for="settings-template-name" class="form-label">Название шаблона</label>
                            <input type="text" class="form-control" id="settings-template-name" 
                                   value="<?php echo e($userTemplate->name ?? $template->name); ?>">
                        </div>
                        
                        <?php if(isset($vipUsers) && count($vipUsers) > 0): ?>
                            <div class="mb-3">
                                <label for="settings-target-user" class="form-label">Предназначен для пользователя</label>
                                <select class="form-select" id="settings-target-user">
                                    <option value="">Для всех пользователей</option>
                                    <?php $__currentLoopData = $vipUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vipUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($vipUser->id); ?>" <?php echo e((isset($userTemplate) && $userTemplate->target_user_id == $vipUser->id) ? 'selected' : ''); ?>>
                                            <?php echo e($vipUser->name); ?> (<?php echo e($vipUser->email); ?>)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="settings-cover-type" class="form-label">Текущая обложка</label>
                            <div class="d-flex align-items-center mb-2">
                                <?php if(session('media_editor_file')): ?>
                                    <div class="border rounded p-2 me-2" style="width: 80px; height: 60px; overflow: hidden;">
                                        <?php if(session('media_editor_type') === 'video'): ?>
                                            <i class="bi bi-film text-primary" style="font-size: 2rem;"></i>
                                        <?php else: ?>
                                            <img src="<?php echo e(asset('storage/template_covers/' . session('media_editor_file'))); ?>" 
                                                alt="Обложка" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    <span id="settings-cover-status" class="text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Обложка загружена
                                    </span>
                                <?php elseif(isset($userTemplate) && $userTemplate->cover_path): ?>
                                    <div class="border rounded p-2 me-2" style="width: 80px; height: 60px; overflow: hidden;">
                                        <?php if($userTemplate->cover_type === 'video'): ?>
                                            <i class="bi bi-film text-primary" style="font-size: 2rem;"></i>
                                        <?php else: ?>
                                            <img src="<?php echo e(asset('storage/template_covers/' . $userTemplate->cover_path)); ?>" 
                                                alt="Обложка" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    <span id="settings-cover-status" class="text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Текущая обложка
                                    </span>
                                <?php else: ?>
                                    <span id="settings-cover-status" class="text-warning">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        Обложка не загружена
                                    </span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo e(isset($template) ? route('media.editor.template', $template->id) : route('media.editor')); ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="bi bi-image me-1"></i>
                                Изменить обложку
                            </a>
                        </div>
                    </div>
                    
                    <!-- Вкладка настроек серии -->
                    <div class="tab-pane fade" id="series" role="tabpanel" aria-labelledby="series-tab">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="settings-is-series" 
                                   <?php echo e((isset($userTemplate) && isset($userTemplate->custom_data['is_series']) && $userTemplate->custom_data['is_series']) ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="settings-is-series">
                                Шаблон является серией
                            </label>
                            <div class="form-text">Серия позволяет выпустить несколько экземпляров одного шаблона</div>
                        </div>
                        
                        <div id="series-settings" class="<?php echo e((isset($userTemplate) && isset($userTemplate->custom_data['is_series']) && $userTemplate->custom_data['is_series']) ? '' : 'd-none'); ?>">
                            <div class="mb-3">
                                <label for="settings-series-quantity" class="form-label">Количество экземпляров</label>
                                <input type="number" class="form-control" id="settings-series-quantity" min="1" 
                                       value="<?php echo e(isset($userTemplate) && isset($userTemplate->custom_data['series_quantity']) ? $userTemplate->custom_data['series_quantity'] : 1); ?>">
                                <div class="form-text">Максимальное количество экземпляров шаблона, которое можно получить</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="settings-required-scans" class="form-label">Требуемое количество сканирований</label>
                                <input type="number" class="form-control" id="settings-required-scans" min="1" 
                                       value="<?php echo e(isset($userTemplate) && isset($userTemplate->custom_data['required_scans']) ? $userTemplate->custom_data['required_scans'] : 1); ?>">
                                <div class="form-text">Количество сканирований, необходимое для завершения цикла сертификата</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Вкладка дополнительных настроек -->
                    <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                        <div class="mb-3">
                            <label for="settings-custom-data" class="form-label">Пользовательские данные (JSON)</label>
                            <textarea class="form-control" id="settings-custom-data" rows="5"><?php echo e(isset($userTemplate) && $userTemplate->custom_data ? json_encode($userTemplate->custom_data, JSON_PRETTY_PRINT) : '{}'); ?></textarea>
                            <div class="form-text">Дополнительные данные в формате JSON для расширенной настройки шаблона</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="settings-export-html" class="form-label">Экспорт HTML</label>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-secondary btn-sm" id="settings-export-html">
                                    <i class="bi bi-file-earmark-code me-1"></i>
                                    Экспортировать HTML
                                </button>
                            </div>
                            <div class="form-text">Выгрузить текущий HTML-код шаблона для редактирования внешними средствами</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-settings-btn">Применить настройки</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const settingsModal = document.getElementById('templateSettingsModal');
    const isSeriesCheckbox = document.getElementById('settings-is-series');
    const seriesSettings = document.getElementById('series-settings');
    const seriesQuantityInput = document.getElementById('settings-series-quantity');
    const requiredScansInput = document.getElementById('settings-required-scans');
    const customDataInput = document.getElementById('settings-custom-data');
    const saveSettingsBtn = document.getElementById('save-settings-btn');
    const targetUserSelect = document.getElementById('settings-target-user');
    const templateNameInput = document.getElementById('settings-template-name');
    const exportHtmlBtn = document.getElementById('settings-export-html');
    
    // Получаем текущие данные
    let customData = {};
    try {
        const customDataFormInput = document.getElementById('custom_data');
        if (customDataFormInput && customDataFormInput.value) {
            customData = JSON.parse(customDataFormInput.value);
        }
    } catch (e) {
        console.error('Ошибка при парсинге пользовательских данных', e);
    }
    
    // Обработчик изменения флага серии
    if (isSeriesCheckbox) {
        isSeriesCheckbox.addEventListener('change', function() {
            if (this.checked) {
                seriesSettings.classList.remove('d-none');
            } else {
                seriesSettings.classList.add('d-none');
            }
        });
    }
    
    // Обработчик клика по кнопке экспорта HTML
    if (exportHtmlBtn) {
        exportHtmlBtn.addEventListener('click', function() {
            const htmlContent = document.getElementById('template-content').innerHTML;
            
            // Создаем элемент для скачивания
            const blob = new Blob([htmlContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'template_export_' + Date.now() + '.html';
            link.click();
            
            // Освобождаем ресурсы
            setTimeout(() => {
                URL.revokeObjectURL(url);
            }, 100);
        });
    }
    
    // Обработчик сохранения настроек
    if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', function() {
            try {
                // Собираем серийные данные
                const isSeries = isSeriesCheckbox && isSeriesCheckbox.checked;
                const seriesQuantity = seriesQuantityInput ? parseInt(seriesQuantityInput.value) || 1 : 1;
                const requiredScans = requiredScansInput ? parseInt(requiredScansInput.value) || 1 : 1;
                
                // Получаем пользовательские данные из JSON
                const userCustomData = customDataInput && customDataInput.value 
                    ? JSON.parse(customDataInput.value)
                    : {};
                
                // Объединяем данные
                const updatedCustomData = {
                    ...userCustomData,
                    is_series: isSeries,
                    series_quantity: seriesQuantity,
                    required_scans: requiredScans
                };
                
                // Обновляем скрытое поле custom_data
                document.getElementById('custom_data').value = JSON.stringify(updatedCustomData);
                
                // Обновляем имя шаблона
                if (templateNameInput) {
                    document.getElementById('template-name').value = templateNameInput.value;
                }
                
                // Обновляем целевого пользователя, если есть
                if (targetUserSelect) {
                    const targetUserInput = document.querySelector('input[name="target_user_id"]');
                    if (!targetUserInput) {
                        // Создаем новый input для целевого пользователя, если его нет
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'target_user_id';
                        input.value = targetUserSelect.value;
                        document.getElementById('template-save-form').appendChild(input);
                    } else {
                        targetUserInput.value = targetUserSelect.value;
                    }
                }
                
                // Закрываем модальное окно
                const modal = bootstrap.Modal.getInstance(settingsModal);
                if (modal) {
                    modal.hide();
                }
                
                // Показываем уведомление об успехе
                alert('Настройки шаблона обновлены. Не забудьте сохранить шаблон!');
                
                // Обновляем элементы в HTML
                updateTemplateElements(updatedCustomData);
                
            } catch (error) {
                console.error('Ошибка при сохранении настроек', error);
                alert('Произошла ошибка при сохранении настроек: ' + error.message);
            }
        });
    }
    
    // Функция для обновления элементов шаблона на основе настроек
    function updateTemplateElements(data) {
        const templateContent = document.getElementById('template-content');
        if (!templateContent) return;
        
        // Обновляем поля серии
        const seriesQuantityElem = templateContent.querySelector('[data-editable="series_quantity"]');
        if (seriesQuantityElem) {
            if (seriesQuantityElem.tagName === 'INPUT') {
                seriesQuantityElem.value = data.series_quantity || 1;
                seriesQuantityElem.placeholder = data.series_quantity || 1;
            } else {
                seriesQuantityElem.textContent = data.series_quantity || 1;
            }
        }
        
        const requiredScansElem = templateContent.querySelector('[data-editable="required_scans"]');
        if (requiredScansElem) {
            if (requiredScansElem.tagName === 'INPUT') {
                requiredScansElem.value = data.required_scans || 1;
                requiredScansElem.placeholder = data.required_scans || 1;
            } else {
                requiredScansElem.textContent = data.required_scans || 1;
            }
        }
        
        // Обновляем название шаблона
        const titleElem = templateContent.querySelector('[data-editable="certificate_title"]');
        if (titleElem && templateNameInput) {
            if (titleElem.tagName === 'INPUT') {
                titleElem.value = templateNameInput.value;
            } else {
                titleElem.textContent = templateNameInput.value;
            }
        }
    }
    
    // Обновляем данные при открытии модального окна
    settingsModal.addEventListener('show.bs.modal', function (event) {
        try {
            // Получаем актуальные данные из скрытого поля
            let currentCustomData = {};
            const customDataFormInput = document.getElementById('custom_data');
            
            if (customDataFormInput && customDataFormInput.value) {
                currentCustomData = JSON.parse(customDataFormInput.value);
            }
            
            // Обновляем поля модального окна
            if (isSeriesCheckbox) {
                isSeriesCheckbox.checked = currentCustomData.is_series || false;
                if (isSeriesCheckbox.checked) {
                    seriesSettings.classList.remove('d-none');
                } else {
                    seriesSettings.classList.add('d-none');
                }
            }
            
            if (seriesQuantityInput) {
                seriesQuantityInput.value = currentCustomData.series_quantity || 1;
            }
            
            if (requiredScansInput) {
                requiredScansInput.value = currentCustomData.required_scans || 1;
            }
            
            if (customDataInput) {
                customDataInput.value = JSON.stringify(currentCustomData, null, 2);
            }
            
            // Обновляем название шаблона из скрытого поля
            if (templateNameInput) {
                const templateNameFormInput = document.getElementById('template-name');
                if (templateNameFormInput) {
                    templateNameInput.value = templateNameFormInput.value;
                }
            }
        } catch (error) {
            console.error('Ошибка при загрузке данных настроек', error);
        }
    });
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/components/modal-template-settings.blade.php ENDPATH**/ ?>