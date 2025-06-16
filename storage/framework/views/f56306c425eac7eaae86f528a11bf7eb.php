

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <!-- Отображение полученных шаблонов в стиле страницы my-templates -->
    <?php
        $acquiredTemplates = Auth::user()->acquiredTemplates()
            ->with('userTemplate.template.category', 'userTemplate.user', 'folder')
            ->latest('created_at')
            ->get();
    ?>
    
    <?php if(session('status')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('status')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if($acquiredTemplates->count() > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h4>Коллекция</h4>
               </div>
    </div>
    
    <!-- Навигация по вкладкам -->
    <ul class="nav nav-tabs card-header-tabs mb-3" id="templateTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button"
                role="tab" aria-controls="all" aria-selected="true">
                <i class="bi bi-grid me-1"></i>
                <span class="tab-text">Все</span>
            </button>
        </li>
        
        
        <!-- Вкладки для папок -->
        <?php $__currentLoopData = $acquiredFolders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link folder-tab d-flex align-items-center" 
                    id="folder-<?php echo e($folder->id); ?>-tab"
                    data-bs-toggle="tab" 
                    data-bs-target="#folder-<?php echo e($folder->id); ?>" 
                    type="button" role="tab"
                    aria-controls="folder-<?php echo e($folder->id); ?>" 
                    aria-selected="false">
                   
                    <span class="ms-1"><?php echo e($folder->name); ?></span>
                    
                   
                </button>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
        <!-- Кнопка создания новой папки -->
        <li class="nav-item">
            <button type="button" class="btn btn-sm text-primary border-0" 
                data-bs-toggle="modal" data-bs-target="#newFolderModal">
                <i class="bi bi-folder-plus"></i>
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="templateTabsContent">
        <!-- Все шаблоны -->
        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
            <div class="row g-2">
                <?php $__currentLoopData = $acquiredTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acquisition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($acquisition->userTemplate): ?> <!-- Проверяем, что шаблон существует -->
                    <div class="col-4">
                        <div class="card h-100 template-card">
                            <!-- Превью карточки -->
                            <div class="card-img-top template-preview">
                                <?php if($acquisition->userTemplate->cover_path): ?>
                                    <?php if($acquisition->userTemplate->cover_type === 'video'): ?>
                                        <video src="<?php echo e(asset('storage/template_covers/'.$acquisition->userTemplate->cover_path)); ?>" 
                                            class="img-fluid" autoplay loop muted></video>
                                    <?php else: ?>
                                        <img src="<?php echo e(asset('storage/template_covers/'.$acquisition->userTemplate->cover_path)); ?>" 
                                            alt="<?php echo e($acquisition->userTemplate->name); ?>" class="img-fluid">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="default-preview d-flex align-items-center justify-content-center">
                                        <i class="bi bi-file-earmark-text template-icon"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Статус шаблона -->
                                <div class="template-status">
                                    <?php if($acquisition->status === 'active'): ?>
                                        <span class="badge bg-success status-badge" title="Активный">✓</span>
                                    <?php elseif($acquisition->status === 'used'): ?>
                                        <span class="badge bg-secondary status-badge" title="Использованный">✓</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Индикатор папки, если шаблон в папке -->
                                <?php if($acquisition->folder_id): ?>
                                <div class="template-folder-indicator">
                                    <span class="badge rounded-pill" style="background-color: <?php echo e($acquisition->folder->color); ?>;">
                                        <i class="bi bi-folder-fill"></i>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Кнопки действий -->
                            <div class="template-actions">
                                <div class="action-buttons">
                                    <a href="<?php echo e(route('public.template', $acquisition->userTemplate->id)); ?>" class="action-btn" title="Просмотреть" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <!-- Кнопка перемещения в папку -->
                                    <button type="button" class="action-btn" title="Переместить в папку" 
                                            data-bs-toggle="modal" data-bs-target="#moveTemplateModal" 
                                            data-template-id="<?php echo e($acquisition->id); ?>" 
                                            data-template-name="<?php echo e($acquisition->userTemplate->name); ?>"
                                            data-current-folder="<?php echo e($acquisition->folder_id ?? ''); ?>">
                                        <i class="bi bi-folder-symlink"></i>
                                    </button>
                                    
                                    <!-- Отображение автора шаблона -->
                                    <div class="template-owner">
                                        <span class="badge bg-dark">
                                            Автор: <?php echo e($acquisition->userTemplate->user->name); ?>

                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        
        <!-- Активные шаблоны -->
        <div class="tab-pane fade" id="active" role="tabpanel" aria-labelledby="active-tab">
            <div class="row g-2">
                <?php $__currentLoopData = $acquiredTemplates->where('status', 'active'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acquisition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($acquisition->userTemplate): ?>
                    <div class="col-4">
                        <div class="card h-100 template-card">
                            <!-- Аналогичное содержимое карточки как выше -->
                            <div class="card-img-top template-preview">
                                <?php if($acquisition->userTemplate->cover_path): ?>
                                    <?php if($acquisition->userTemplate->cover_type === 'video'): ?>
                                        <video src="<?php echo e(asset('storage/template_covers/'.$acquisition->userTemplate->cover_path)); ?>" 
                                            class="img-fluid" autoplay loop muted></video>
                                    <?php else: ?>
                                        <img src="<?php echo e(asset('storage/template_covers/'.$acquisition->userTemplate->cover_path)); ?>" 
                                            alt="<?php echo e($acquisition->userTemplate->name); ?>" class="img-fluid">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="default-preview d-flex align-items-center justify-content-center">
                                        <i class="bi bi-file-earmark-text template-icon"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="template-status">
                                    <span class="badge bg-success status-badge" title="Активный">✓</span>
                                </div>
                                
                                <!-- Индикатор папки, если шаблон в папке -->
                                <?php if($acquisition->folder_id): ?>
                                <div class="template-folder-indicator">
                                    <span class="badge rounded-pill" style="background-color: <?php echo e($acquisition->folder->color); ?>;">
                                        <i class="bi bi-folder-fill"></i>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="template-actions">
                                <div class="action-buttons">
                                    <a href="<?php echo e(route('public.template', $acquisition->userTemplate->id)); ?>" class="action-btn" title="Просмотреть" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <!-- Кнопка перемещения в папку -->
                                    <button type="button" class="action-btn" title="Переместить в папку" 
                                            data-bs-toggle="modal" data-bs-target="#moveTemplateModal" 
                                            data-template-id="<?php echo e($acquisition->id); ?>" 
                                            data-template-name="<?php echo e($acquisition->userTemplate->name); ?>"
                                            data-current-folder="<?php echo e($acquisition->folder_id ?? ''); ?>">
                                        <i class="bi bi-folder-symlink"></i>
                                    </button>
                                    
                                    <div class="template-owner">
                                        <span class="badge bg-dark">
                                            Автор: <?php echo e($acquisition->userTemplate->user->name); ?>

                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        
        <!-- Использованные шаблоны -->
        <div class="tab-pane fade" id="used" role="tabpanel" aria-labelledby="used-tab">
            <div class="row g-2">
                <?php $__currentLoopData = $acquiredTemplates->where('status', 'used'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acquisition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($acquisition->userTemplate): ?>
                    <div class="col-4">
                        <div class="card h-100 template-card">
                            <!-- Аналогичное содержимое карточки как выше -->
                            <div class="card-img-top template-preview">
                                <?php if($acquisition->userTemplate->cover_path): ?>
                                    <?php if($acquisition->userTemplate->cover_type === 'video'): ?>
                                        <video src="<?php echo e(asset('storage/template_covers/'.$acquisition->userTemplate->cover_path)); ?>" 
                                            class="img-fluid" autoplay loop muted></video>
                                    <?php else: ?>
                                        <img src="<?php echo e(asset('storage/template_covers/'.$acquisition->userTemplate->cover_path)); ?>" 
                                            alt="<?php echo e($acquisition->userTemplate->name); ?>" class="img-fluid">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="default-preview d-flex align-items-center justify-content-center">
                                        <i class="bi bi-file-earmark-text template-icon"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="template-status">
                                    <span class="badge bg-secondary status-badge" title="Использованный">✓</span>
                                </div>
                                
                                <!-- Индикатор папки, если шаблон в папке -->
                                <?php if($acquisition->folder_id): ?>
                                <div class="template-folder-indicator">
                                    <span class="badge rounded-pill" style="background-color: <?php echo e($acquisition->folder->color); ?>;">
                                        <i class="bi bi-folder-fill"></i>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="template-actions">
                                <div class="action-buttons">
                                    <a href="<?php echo e(route('public.template', $acquisition->userTemplate->id)); ?>" class="action-btn" title="Просмотреть" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <!-- Кнопка перемещения в папку -->
                                    <button type="button" class="action-btn" title="Переместить в папку" 
                                            data-bs-toggle="modal" data-bs-target="#moveTemplateModal" 
                                            data-template-id="<?php echo e($acquisition->id); ?>" 
                                            data-template-name="<?php echo e($acquisition->userTemplate->name); ?>"
                                            data-current-folder="<?php echo e($acquisition->folder_id ?? ''); ?>">
                                        <i class="bi bi-folder-symlink"></i>
                                    </button>
                                    
                                    <div class="template-owner">
                                        <span class="badge bg-dark">
                                            Автор: <?php echo e($acquisition->userTemplate->user->name); ?>

                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        
        <!-- Шаблоны по папкам -->
        <?php $__currentLoopData = $acquiredFolders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="tab-pane fade" id="folder-<?php echo e($folder->id); ?>" role="tabpanel" aria-labelledby="folder-<?php echo e($folder->id); ?>-tab">
                <div class="row g-2">
                    <?php $folderTemplates = $acquiredTemplates->where('folder_id', $folder->id); ?>
                    
                    <?php if($folderTemplates->count() > 0): ?>
                        <?php $__currentLoopData = $folderTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acquisition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($acquisition->userTemplate): ?>
                            <div class="col-4">
                                <div class="card h-100 template-card">
                                    <!-- Превью карточки -->
                                    <div class="card-img-top template-preview">
                                        <?php if($acquisition->userTemplate->cover_path): ?>
                                            <?php if($acquisition->userTemplate->cover_type === 'video'): ?>
                                                <video src="<?php echo e(asset('storage/template_covers/'.$acquisition->userTemplate->cover_path)); ?>" 
                                                    class="img-fluid" autoplay loop muted></video>
                                            <?php else: ?>
                                                <img src="<?php echo e(asset('storage/template_covers/'.$acquisition->userTemplate->cover_path)); ?>" 
                                                    alt="<?php echo e($acquisition->userTemplate->name); ?>" class="img-fluid">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="default-preview d-flex align-items-center justify-content-center">
                                                <i class="bi bi-file-earmark-text template-icon"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Статус шаблона -->
                                        <div class="template-status">
                                            <?php if($acquisition->status === 'active'): ?>
                                                <span class="badge bg-success status-badge" title="Активный">✓</span>
                                            <?php elseif($acquisition->status === 'used'): ?>
                                                <span class="badge bg-secondary status-badge" title="Использованный">✓</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="template-folder-indicator">
                                            <span class="badge rounded-pill" style="background-color: <?php echo e($folder->color); ?>;">
                                                <i class="bi bi-folder-fill"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Кнопки действий -->
                                    <div class="template-actions">
                                        <div class="action-buttons">
                                            <a href="<?php echo e(route('public.template', $acquisition->userTemplate->id)); ?>" class="action-btn" title="Просмотреть" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            <!-- Кнопка перемещения в папку -->
                                            <button type="button" class="action-btn" title="Переместить в папку" 
                                                    data-bs-toggle="modal" data-bs-target="#moveTemplateModal" 
                                                    data-template-id="<?php echo e($acquisition->id); ?>" 
                                                    data-template-name="<?php echo e($acquisition->userTemplate->name); ?>"
                                                    data-current-folder="<?php echo e($folder->id); ?>">
                                                <i class="bi bi-folder-symlink"></i>
                                            </button>
                                            
                                            <div class="template-owner">
                                                <span class="badge bg-dark">
                                                    Автор: <?php echo e($acquisition->userTemplate->user->name); ?>

                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-folder text-center">
                                <div class="empty-folder-icon">
                                    <i class="bi bi-folder2-open"></i>
                                </div>
                                <h4 class="text-muted">Папка пуста</h4>
                                <p>Перетащите шаблоны в эту папку, нажав на значок <i class="bi bi-folder-symlink"></i></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    
    <!-- Модальное окно для создания новой папки -->
    <div class="modal fade" id="newFolderModal" tabindex="-1" aria-labelledby="newFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newFolderModalLabel">Создать новую папку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo e(route('acquired.folders.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="folder-name" class="form-label">Название папки</label>
                            <input type="text" class="form-control" id="folder-name" name="name" required>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Создать папку</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для редактирования папки -->
    <div class="modal fade" id="editFolderModal" tabindex="-1" aria-labelledby="editFolderModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFolderModalLabel">Изменить папку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-folder-form" action="" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit-folder-name" class="form-label">Название папки</label>
                            <input type="text" class="form-control" id="edit-folder-name" name="name" required>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для удаления папки -->
    <div class="modal fade" id="deleteFolderModal" tabindex="-1" aria-labelledby="deleteFolderModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFolderModalLabel">Удалить папку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить папку <strong id="delete-folder-name"></strong>?</p>
                    <p class="text-muted">Шаблоны из этой папки не будут удалены и станут доступны в общем списке.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <form id="delete-folder-form" action="" method="POST">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для перемещения шаблона в папку -->
    <div class="modal fade" id="moveTemplateModal" tabindex="-1" aria-labelledby="moveTemplateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveTemplateModalLabel">Переместить шаблон</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="move-template-form" action="<?php echo e(route('acquired.templates.move')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="template_id" id="move-template-id" value="">
                    <div class="modal-body">
                        <p>Выберите папку для шаблона <strong id="move-template-name"></strong>:</p>

                        <div class="list-group">
                            <label class="list-group-item">
                                <input class="form-check-input me-2" type="radio" name="folder_id" value=""
                                    checked>
                                <i class="bi bi-folder text-muted me-2"></i> Без папки
                            </label>

                            <?php $__currentLoopData = $acquiredFolders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="list-group-item">
                                    <input class="form-check-input me-2" type="radio" name="folder_id"
                                        value="<?php echo e($folder->id); ?>">
                                    <i class="bi bi-folder-fill me-2" style="color: <?php echo e($folder->color); ?>;"></i>
                                    <?php echo e($folder->name); ?>

                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Переместить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        /* Стилизация видео в карточках */
        .template-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        /* Стилизация пустого списка */
        .empty-folder {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-folder-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        /* Стили для владельца шаблона */
        .template-owner {
            position: absolute;
            bottom: 5px;
            left: 5px;
            z-index: 2;
        }
        
        .template-owner .badge {
            font-size: 10px;
            font-weight: normal;
            padding: 4px 8px;
            border-radius: 10px;
            opacity: 0.9;
        }
        
        /* Стили для индикатора папки */
        .template-folder-indicator {
            position: absolute;
            top: 5px;
            left: 5px;
            z-index: 2;
        }
        
        .template-folder-indicator .badge {
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 10px;
            opacity: 0.9;
        }
        
        /* Адаптивные стили для вкладок */
        @media (max-width: 767px) {
            .tab-text {
                display: none;
            }
            
            .nav-link .bi {
                margin-right: 0 !important;
                font-size: 1rem;
            }
            
            .nav-item {
                margin: 0 2px;
            }
            
            .nav-tabs .nav-link {
                padding: 0.2rem 0.5rem;
            }
            
            .folder-tab .dropdown {
                margin-left: 5px !important;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Управление предпросмотром цвета папки при создании
            const folderColor = document.getElementById('folder-color');
            const folderColorPreview = document.getElementById('folder-color-preview');
            
            if (folderColor && folderColorPreview) {
                folderColor.addEventListener('input', function() {
                    folderColorPreview.style.color = this.value;
                });
            }
            
            // Управление предпросмотром цвета папки при редактировании
            const editFolderColor = document.getElementById('edit-folder-color');
            const editFolderColorPreview = document.getElementById('edit-folder-color-preview');
            
            if (editFolderColor && editFolderColorPreview) {
                editFolderColor.addEventListener('input', function() {
                    editFolderColorPreview.style.color = this.value;
                });
            }
            
            // Заполнение данных для редактирования папки
            document.querySelectorAll('[data-bs-target="#editFolderModal"]').forEach(element => {
                element.addEventListener('click', function() {
                    const folderId = this.getAttribute('data-folder-id');
                    const folderName = this.getAttribute('data-folder-name');
                    const folderColor = this.getAttribute('data-folder-color');
                    
                    document.getElementById('edit-folder-name').value = folderName;
                    document.getElementById('edit-folder-color').value = folderColor;
                    document.getElementById('edit-folder-color-preview').style.color = folderColor;
                    document.getElementById('edit-folder-form').action = `/client/acquired-folders/${folderId}`;
                });
            });
            
            // Заполнение данных для удаления папки
            document.querySelectorAll('[data-bs-target="#deleteFolderModal"]').forEach(element => {
                element.addEventListener('click', function() {
                    const folderId = this.getAttribute('data-folder-id');
                    const folderName = this.getAttribute('data-folder-name');
                    
                    document.getElementById('delete-folder-name').textContent = folderName;
                    document.getElementById('delete-folder-form').action = `/client/acquired-folders/${folderId}`;
                });
            });
            
            // Заполнение данных для перемещения шаблона
            document.querySelectorAll('[data-bs-target="#moveTemplateModal"]').forEach(element => {
                element.addEventListener('click', function() {
                    const templateId = this.getAttribute('data-template-id');
                    const templateName = this.getAttribute('data-template-name');
                    const currentFolder = this.getAttribute('data-current-folder');
                    
                    document.getElementById('move-template-id').value = templateId;
                    document.getElementById('move-template-name').textContent = templateName;
                    
                    // Устанавливаем текущую папку в форме
                    if (currentFolder) {
                        const radioButton = document.querySelector(
                            `input[name="folder_id"][value="${currentFolder}"]`);
                        if (radioButton) radioButton.checked = true;
                    } else {
                        document.querySelector('input[name="folder_id"][value=""]').checked = true;
                    }
                });
            });
        });
    </script>
    <?php else: ?>
        <div class="alert alert-info">
            У вас пока нет полученных шаблонов.
        </div>
    <?php endif; ?>
</div>
 
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/home.blade.php ENDPATH**/ ?>