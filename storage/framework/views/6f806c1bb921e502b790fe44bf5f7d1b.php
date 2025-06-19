

<?php $__env->startSection('content'); ?>
<div class="container py-4">
 
    <!-- Отладочная информация -->
    <?php if(config('app.debug')): ?>
    <div class="alert alert-info mb-4">
        <h5>Диагностическая информация:</h5>
        <p>Авторизованный пользователь: <?php echo e(Auth::user()->name); ?> (ID: <?php echo e(Auth::user()->id); ?>)</p>
        <p>Всего найденных шаблонов: <?php echo e($templates->count()); ?></p>
        <p>SQL запрос: <code>SELECT * FROM user_templates WHERE user_id = <?php echo e(Auth::user()->id); ?></code></p>
    </div>
    <?php endif; ?>
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

    <!-- Навигация по вкладкам -->
    <ul class="nav nav-tabs mb-3" id="templateTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button"
                role="tab" aria-controls="all" aria-selected="true">
                <i class="bi bi-grid me-1"></i> Все
            </button>
        </li>
        
        <?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="folder-<?php echo e($folder->id); ?>-tab" data-bs-toggle="tab" 
                    data-bs-target="#folder-<?php echo e($folder->id); ?>" type="button" role="tab"
                    aria-controls="folder-<?php echo e($folder->id); ?>" aria-selected="false">
                    <i class="bi bi-folder-fill me-1" style="color: <?php echo e($folder->color); ?>;"></i> 
                    <?php echo e($folder->name); ?>

                </button>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
        <li class="nav-item ms-auto">
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newFolderModal">
                <i class="bi bi-folder-plus me-1"></i> Новая папка
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
            <?php if($templates->count() > 0): ?>
                <?php echo $__env->make('user.templates.partials.template-list', ['templates' => $templates], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php else: ?>
              
            <?php endif; ?>
        </div>
        
        <?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="tab-pane fade" id="folder-<?php echo e($folder->id); ?>" role="tabpanel" aria-labelledby="folder-<?php echo e($folder->id); ?>-tab">
                <?php $folderTemplates = $templates->where('folder_id', $folder->id); ?>
                
                <?php if($folderTemplates->count() > 0): ?>
                    <?php echo $__env->make('user.templates.partials.template-list', ['templates' => $folderTemplates], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php else: ?>
                    <div class="empty-folder text-center py-5">
                        <div class="empty-folder-icon">
                            <i class="bi bi-folder"></i>
                        </div>
                        <h4>Папка пуста</h4>
                        <p class="text-muted">В этой папке пока нет шаблонов</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>


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
    scrollbar-width: none;
    -ms-overflow-style: none;
    overflow-y: hidden;
    scrollbar-width: thin;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
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
       
    </style>
<?php $__env->stopSection(); ?>
                          
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/user/templates/index.blade.php ENDPATH**/ ?>