

<?php $__env->startSection('content'); ?>
    <div class="">
       <div class="text-center overflow-hidden position-relative" style="padding: 15px">
        <div class="blur-gradient-effect">
            <img src="<?php echo e(isset($profileUser) ? ($profileUser->avatar ? asset('storage/avatars/'.$profileUser->avatar) : asset('images/default-avatar.jpg')) : (Auth::user()->avatar ? asset('storage/avatars/'.Auth::user()->avatar) : asset('images/default-avatar.jpg'))); ?>"
                class="profile-avatar" alt="Аватар">
                
        </div>
        <div class="abs_title_img">
         <h4 class="mt-3 user-name-display"><?php echo e(isset($profileUser) ? $profileUser->name : Auth::user()->name); ?></h4>
         <p class="text-muted"><?php echo e(isset($profileUser) ? $profileUser->email : Auth::user()->email); ?></p>
        </div>
       </div>
        <?php if(session('status')): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo e(session('status')); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
       
    </div>

    <div class="card-body">
         <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button"
                    role="tab" aria-controls="all" aria-selected="true">
                    <i class="bi bi-grid me-1"></i>
                   
                </button>
            </li>
          
            <?php if(!isset($isOwner) || $isOwner !== false): ?>
            <!-- Для папок добавляем тот же стиль -->
            <?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="nav-item" role="presentation">
                    <button class="folder-tab d-flex align-items-center" id="folder-<?php echo e($folder->id); ?>-tab"
                        data-bs-toggle="tab" data-bs-target="#folder-<?php echo e($folder->id); ?>" type="button" role="tab"
                        aria-controls="folder-<?php echo e($folder->id); ?>" aria-selected="false">
                       
                        <span class="ms-1"><?php echo e($folder->name); ?></span>

                    </button>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              <button type="button" class="me-1" style="border: none; background: none;" data-bs-toggle="modal"
                data-bs-target="#newFolderModal">
                <i class="bi bi-folder-plus"></i>
            </button>
            <?php endif; ?>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- Все шаблоны -->
            <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                <?php echo $__env->make('user.templates.partials.template-list', [
                    'templates' => $userTemplates,
                    'isOwner' => isset($isOwner) ? $isOwner : true
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>

            <?php if(!isset($isOwner) || $isOwner !== false): ?>
            <!-- Опубликованные шаблоны -->
            <div class="tab-pane fade" id="published" role="tabpanel" aria-labelledby="published-tab">
                <?php echo $__env->make('user.templates.partials.template-list', [
                    'templates' => $userTemplates->where('status', 'published'),
                    'isOwner' => isset($isOwner) ? $isOwner : true
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>

            <!-- Черновики -->
            <div class="tab-pane fade" id="draft" role="tabpanel" aria-labelledby="draft-tab">
                <?php echo $__env->make('user.templates.partials.template-list', [
                    'templates' => $userTemplates->where('status', 'draft'),
                    'isOwner' => isset($isOwner) ? $isOwner : true
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>

            <!-- Шаблоны по папкам -->
            <?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="tab-pane fade" id="folder-<?php echo e($folder->id); ?>" role="tabpanel"
                    aria-labelledby="folder-<?php echo e($folder->id); ?>-tab">
                 
                    <?php echo $__env->make('user.templates.partials.template-list', [
                        'templates' => $userTemplates->where('folder_id', $folder->id),
                        'currentFolder' => $folder,
                        'isOwner' => isset($isOwner) ? $isOwner : true
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>

    </div>

    <?php if(!isset($isOwner) || $isOwner !== false): ?>
    <!-- Модальное окно для создания новой папки -->
    <div class="modal fade" id="newFolderModal" tabindex="-1" aria-labelledby="newFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newFolderModalLabel">Создать новую папку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo e(route('client.folders.store')); ?>" method="POST">
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

    <!-- Модальное окно для перемещения шаблона -->
    <div class="modal fade" id="moveTemplateModal" tabindex="-1" aria-labelledby="moveTemplateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveTemplateModalLabel">Переместить шаблон</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="move-template-form" action="" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <p>Выберите папку для шаблона <strong id="move-template-name"></strong>:</p>

                        <div class="list-group">
                            <label class="list-group-item">
                                <input class="form-check-input me-2" type="radio" name="folder_id" value=""
                                    checked>
                                <i class="bi bi-folder text-muted me-2"></i> Без папки
                            </label>

                            <?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="list-group-item">
                                    <input class="form-check-input me-2" type="radio" name="folder_id"
                                        value="<?php echo e($folder->id); ?>">
                                    <i class="bi bi-folder-fill me-2" style="color: <?php echo e($folder->color ?? '#6c757d'); ?>;"></i>
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
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-template');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const templateId = this.getAttribute('data-id');
                    const templateName = this.getAttribute('data-name');
                    
                    document.getElementById('template-name-to-delete').textContent = templateName;
                    document.getElementById('delete-template-form').action = `/client/my-templates/${templateId}`;
                });
            });

            // Добавляем обработчики для свайпа между папками
            const tabsContainer = document.querySelector('#myTabContent');
            let touchStartX = 0;
            let touchEndX = 0;
            let currentTabIndex = 0;
            
            // Получаем все вкладки
            const tabButtons = Array.from(document.querySelectorAll('#myTab button'));
            
            // Находим активную вкладку
            const activeTabButton = document.querySelector('#myTab button.active');
            if (activeTabButton) {
                currentTabIndex = tabButtons.indexOf(activeTabButton);
            }

            if (tabsContainer) {
                // Обработчик начала касания
                tabsContainer.addEventListener('touchstart', function(e) {
                    touchStartX = e.changedTouches[0].screenX;
                }, { passive: true });
                
                // Обработчик окончания касания
                tabsContainer.addEventListener('touchend', function(e) {
                    touchEndX = e.changedTouches[0].screenX;
                    handleGesture();
                }, { passive: true });
            }
            
            // Обработка жеста свайпа
            function handleGesture() {
                // Вычисляем минимальное расстояние для регистрации свайпа (20% от ширины экрана)
                const minSwipeDistance = window.innerWidth * 0.2;
                
                if (touchEndX - touchStartX > minSwipeDistance) {
                    // Свайп вправо - переключаемся на предыдущую вкладку
                    if (currentTabIndex > 0) {
                        currentTabIndex--;
                        activateTab(currentTabIndex);
                    }
                } else if (touchStartX - touchEndX > minSwipeDistance) {
                    // Свайп влево - переключаемся на следующую вкладку
                    if (currentTabIndex < tabButtons.length - 1) {
                        currentTabIndex++;
                        activateTab(currentTabIndex);
                    }
                }
            }
            
            // Активация вкладки с определенным индексом
            function activateTab(index) {
                if (tabButtons[index]) {
                    // Создаем новый экземпляр bootstrap.Tab и активируем его
                    const tab = new bootstrap.Tab(tabButtons[index]);
                    tab.show();
                    
                    // Прокручиваем вкладку в зону видимости
                    tabButtons[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    
                    // Обновляем текущий индекс
                    currentTabIndex = index;
                    
                    // Показываем визуальное подтверждение свайпа (небольшая анимация)
                    showSwipeAnimation(index);
                }
            }
            
            // Показываем визуальную индикацию свайпа
            function showSwipeAnimation(index) {
                const content = document.querySelector(tabButtons[index].dataset.bsTarget);
                if (content) {
                    // Добавляем класс для анимации и через 500мс удаляем его
                    content.classList.add('tab-swiped');
                    setTimeout(() => {
                        content.classList.remove('tab-swiped');
                    }, 500);
                }
            }
            
            // Добавляем стили для анимации свайпа
            const style = document.createElement('style');
            style.textContent = `
                .tab-pane.tab-swiped {
                    animation: tab-swipe-in 0.5s ease;
                }
                @keyframes tab-swipe-in {
                    0% { opacity: 0.7; transform: translateX(5%); }
                    100% { opacity: 1; transform: translateX(0); }
                }
                
                /* Улучшаем прокрутку для вкладок */
                .nav-tabs {
                    flex-wrap: nowrap;
                    overflow-x: auto;
                    overflow-y: hidden;
                    scrollbar-width: thin;
                    -ms-overflow-style: none;
                    scroll-behavior: smooth;
                    -webkit-overflow-scrolling: touch;
                }
                .nav-tabs::-webkit-scrollbar {
                    height: 4px;
                }
                .nav-tabs::-webkit-scrollbar-thumb {
                    background-color: rgba(0, 0, 0, 0.2);
                }
            `;
            document.head.append(style);
            
            // Обрабатываем изменение вкладки по клику для обновления индекса
            tabButtons.forEach((button, index) => {
                button.addEventListener('shown.bs.tab', function() {
                    currentTabIndex = index;
                });
            });
        });
    </script>

    <!-- Добавляем стили для адаптивных вкладок -->
    <style>
        /* Визуальные индикаторы для свайпа */
        .swipe-indicator {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(0, 0, 0, 0.2);
            font-size: 2rem;
            display: flex;
            align-items: center;
            padding: 20px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .swipe-indicator-left {
            left: 0;
        }
        .swipe-indicator-right {
            right: 0;
        }
        
        .tab-content {
            position: relative;
            min-height: 100vh;
        }
        
        /* При первой загрузке показываем подсказку о свайпе */
        .tab-content:not(.swiped-before):hover .swipe-indicator {
            opacity: 0.6;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.2; }
            50% { opacity: 0.6; }
            100% { opacity: 0.2; }
        }
    </style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/user/templates/index.blade.php ENDPATH**/ ?>