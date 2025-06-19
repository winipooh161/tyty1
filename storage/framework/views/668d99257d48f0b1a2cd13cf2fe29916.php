<?php if($templates->count() > 0): ?>
<div class="row g-2">
    <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-4">
        <a href="<?php echo e(route('public.template', $template->id)); ?>" class="text-decoration-none template-card-link">
            <div class="card h-100 template-card">
                <!-- Превью карточки (если есть) -->
                <div class="card-img-top template-preview">
                    <?php if($template->cover_path): ?>
                        <?php if($template->cover_type === 'video'): ?>
                            <video src="<?php echo e(asset('storage/' . $template->cover_path)); ?>" class="img-fluid" autoplay loop muted></video>
                        <?php else: ?>
                            <img src="<?php echo e(asset('storage/' . $template->cover_path)); ?>" alt="<?php echo e($template->name); ?>" class="img-fluid">
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="default-preview d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-text template-icon"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Индикатор VIP-пользователя -->
                    <?php if($template->target_user_id): ?>
                        <div class="template-vip-indicator">
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-person-circle"></i> VIP
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Если шаблон предназначен для VIP-пользователя, показываем информацию о нём -->
                <?php if($template->target_user_id): ?>
                    <div class="card-footer p-2">
                        <small class="text-muted">
                            Для: <?php echo e($template->targetUser->name); ?>

                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </a>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<style>
    /* Стили для индикатора папки */
    .template-folder-indicator {
        position: absolute;
        bottom: 5px;
        left: 5px;
        z-index: 2;
    }
    
    /* Стили для индикатора VIP-пользователя */
    .template-vip-indicator {
        position: absolute;
        top: 5px;
        left: 5px;
        z-index: 2;
    }
    
    /* Стилизация видео в карточках */
    .template-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }
    
    /* Новые стили для кликабельной карточки */
    .template-card-link {
        display: block;
        color: inherit;
    }
    
    .template-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .template-card-link:hover .template-card {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    /* Стили для пустых карточек */
    .empty-template-card {
   
        opacity: 0.6;
        cursor: default;
        pointer-events: none;
    }
    
    .empty-preview {
        background-color: #ffffff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 150px;
    }
    
    .empty-card-content {
        width: 100%;
        height: 100%;
        background-color: transparent;
    }
    
    /* Убираем эффект hover для пустых карточек */
    .empty-template-card:hover {
        transform: none;
        box-shadow: none;
    }
</style>

<?php else: ?>
<!-- Пустые карточки вместо сообщения о пустой папке -->
<div class="row g-2">
    <?php for($i = 0; $i < 12; $i++): ?>
    <div class="col-4">
        <div class="card h-100 template-card empty-template-card">
            <div class="card-img-top template-preview empty-preview">
                <div class="empty-card-content">
                    <!-- Пустая карточка -->
                </div>
            </div>
        </div>
    </div>
    <?php endfor; ?>
</div>

<style>
    /* Стили для пустых карточек */
    .empty-template-card {
        border: 2px dashed #e9ecef;
        background-color: #f8f9fa;
        opacity: 0.6;
        cursor: default;
        pointer-events: none;
    }
    
    .empty-preview {
        background-color: #ffffff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 150px;
    }
    
    .empty-card-content {
        width: 100%;
        height: 100%;
        background-color: transparent;
    }
    
    /* Убираем эффект hover для пустых карточек */
    .empty-template-card:hover {
        transform: none;
        box-shadow: none;
    }
</style>
<?php endif; ?>

<?php if(!isset($isOwner) || $isOwner === true): ?>
<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTemplateModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить шаблон <strong id="template-name-to-delete"></strong>?</p>
                <p class="text-danger">Это действие нельзя будет отменить.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form id="delete-template-form" action="" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>

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
});
</script>

<?php else: ?>
<div class="empty-folder text-center">
    <div class="empty-folder-icon">
        <i class="bi bi-folder2-open"></i>
    </div>
    <h4 class="text-muted">Папка пуста</h4>
</div>
<?php endif; ?>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/user/templates/partials/template-list.blade.php ENDPATH**/ ?>