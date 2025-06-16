

<?php $__env->startSection('styles'); ?>
<!-- Добавляем стили для Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="mb-4 d-flex align-items-center">
                <h2>Редактирование шаблона: <?php echo e($template->name); ?></h2>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <form action="<?php echo e(route('admin.templates.update', $template->id)); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Название шаблона *</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="name" name="name" value="<?php echo e(old('name', $template->name)); ?>" required>
                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="template_category_id" class="form-label">Категория *</label>
                                <select class="form-select <?php $__errorArgs = ['template_category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="template_category_id" name="template_category_id" required>
                                    <option value="">Выберите категорию</option>
                                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category->id); ?>" <?php echo e((old('template_category_id', $template->template_category_id) == $category->id) ? 'selected' : ''); ?>><?php echo e($category->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['template_category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        
                        <!-- Поле выбора VIP-пользователя с поиском -->
                        <div class="mb-3">
                            <label for="target_user_id" class="form-label">Предназначен для VIP-пользователя</label>
                            <select class="form-select select2-user-search <?php $__errorArgs = ['target_user_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="target_user_id" name="target_user_id" data-placeholder="Для всех пользователей">
                                <option value="">Для всех пользователей</option>
                                <?php $__currentLoopData = $vipUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($user->id); ?>" <?php echo e(old('target_user_id', $template->target_user_id) == $user->id ? 'selected' : ''); ?>>
                                    ID: <?php echo e($user->id); ?> | <?php echo e($user->name); ?> | <?php echo e($user->email); ?>

                                </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <div class="form-text">Если выбран пользователь, то шаблон будет доступен только для него. Поиск работает по ID, имени и email.</div>
                            <?php $__errorArgs = ['target_user_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="description" name="description" rows="3"><?php echo e(old('description', $template->description)); ?></textarea>
                            <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="html_content" class="form-label">HTML содержимое шаблона *</label>
                            <textarea class="form-control <?php $__errorArgs = ['html_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="html_content" name="html_content" rows="10" required><?php echo e(old('html_content', $template->html_content)); ?></textarea>
                            <div class="form-text">
                                Используйте атрибут <code>data-editable="field-name"</code> для элементов, которые пользователь сможет редактировать.
                                Например: <code>&lt;h1 data-editable="title"&gt;Заголовок&lt;/h1&gt;</code>
                            </div>
                            <?php $__errorArgs = ['html_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editable_fields" class="form-label">Редактируемые поля (JSON)</label>
                            <textarea class="form-control <?php $__errorArgs = ['editable_fields'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="editable_fields" name="editable_fields" rows="5"><?php echo e(old('editable_fields', json_encode($template->editable_fields, JSON_PRETTY_PRINT))); ?></textarea>
                            <div class="form-text">
                                Укажите JSON-объект, где ключи - это названия полей из атрибутов data-editable, 
                                а значения - описания этих полей для пользователя.<br>
                                Пример: <code>{"title": "Заголовок приглашения", "date": "Дата мероприятия"}</code>
                            </div>
                            <?php $__errorArgs = ['editable_fields'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="preview_image" class="form-label">Изображение превью</label>
                            <?php if($template->preview_image): ?>
                            <div class="mb-2">
                                <img src="<?php echo e(asset('storage/template_previews/'.$template->preview_image)); ?>" alt="<?php echo e($template->name); ?>" class="img-thumbnail" style="max-height: 150px;">
                                <div class="form-text">Текущее изображение</div>
                            </div>
                            <?php endif; ?>
                            <input type="file" class="form-control <?php $__errorArgs = ['preview_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="preview_image" name="preview_image" accept="image/*">
                            <div class="form-text">Загрузите новое изображение, если хотите заменить текущее. Рекомендуемый размер: 600x400px, максимальный размер: 2MB</div>
                            <?php $__errorArgs = ['preview_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="display_order" class="form-label">Порядок отображения</label>
                                <input type="number" class="form-control <?php $__errorArgs = ['display_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="display_order" name="display_order" value="<?php echo e(old('display_order', $template->display_order)); ?>" min="0">
                                <?php $__errorArgs = ['display_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo e(old('is_active', $template->is_active) ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="is_active">
                                        Активный шаблон
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" <?php echo e(old('is_default', $template->is_default) ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="is_default">
                                        Стандартный шаблон для категории (доступен для всех пользователей)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php echo e(route('admin.templates.index')); ?>" class="btn btn-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация Select2
    $(document).ready(function() {
        $('.select2-user-search').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: 'Выберите пользователя или начните вводить для поиска',
            matcher: function(params, data) {
                // Если пустой запрос, возвращаем все данные
                if ($.trim(params.term) === '') {
                    return data;
                }

                // Не выполняем поиск, если нет текста
                if (typeof data.text === 'undefined') {
                    return null;
                }

                // Поиск по ID, имени и email
                var dataText = data.text.toLowerCase();
                var searchText = params.term.toLowerCase();
                
                // Проверяем, содержит ли строка searchText
                if (dataText.indexOf(searchText) > -1) {
                    return data;
                }

                // Если не найдено совпадений
                return null;
            }
        });
    });
    
    // Функция для извлечения редактируемых полей
    function extractEditableFields() {
        const htmlContent = document.getElementById('html_content').value;
        const regex = /data-editable=["']([^"']+)["']/g;
        let match;
        const fields = {};
        
        while ((match = regex.exec(htmlContent)) !== null) {
            const fieldName = match[1];
            if (!fields[fieldName]) {
                fields[fieldName] = `Описание поля "${fieldName}"`;
            }
        }
        
        // Получаем текущие поля
        let currentFields = {};
        try {
            currentFields = JSON.parse(document.getElementById('editable_fields').value);
        } catch (e) {
            currentFields = {};
        }
        
        // Объединяем существующие описания с новыми полями
        const result = {};
        Object.keys(fields).forEach(key => {
            result[key] = currentFields[key] || fields[key];
        });
        
        document.getElementById('editable_fields').value = JSON.stringify(result, null, 2);
    }

    // Добавляем кнопку для извлечения полей
    const htmlTextarea = document.getElementById('html_content');
    const extractButton = document.createElement('button');
    extractButton.type = 'button';
    extractButton.className = 'btn btn-sm btn-outline-secondary mt-2';
    extractButton.textContent = 'Извлечь редактируемые поля';
    extractButton.addEventListener('click', function(e) {
        e.preventDefault();
        extractEditableFields();
    });
    
    htmlTextarea.parentNode.insertBefore(extractButton, htmlTextarea.nextSibling);
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/admin/templates/edit.blade.php ENDPATH**/ ?>