

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h2>Управление категориями шаблонов</h2>
        <a href="<?php echo e(route('admin.template-categories.create')); ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Добавить категорию
        </a>
    </div>

    <?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th width="100">Изображение</th>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Описание</th>
                            <th>Порядок</th>
                            <th>Статус</th>
                            <th width="200">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($category->id); ?></td>
                            <td>
                                <?php if($category->image): ?>
                                <img src="<?php echo e(asset('storage/category_images/'.$category->image)); ?>" 
                                     alt="<?php echo e($category->name); ?>" class="img-thumbnail" style="max-height: 50px;">
                                <?php else: ?>
                                <span class="text-muted">Нет</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($category->name); ?></td>
                            <td><code><?php echo e($category->slug); ?></code></td>
                            <td><?php echo e(Str::limit($category->description, 50)); ?></td>
                            <td><?php echo e($category->display_order); ?></td>
                            <td>
                                <?php if($category->is_active): ?>
                                <span class="badge bg-success">Активна</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Неактивна</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="<?php echo e(route('client.templates.index', $category->slug)); ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?php echo e(route('admin.template-categories.edit', $category->id)); ?>" 
                                       class="btn btn-sm btn-outline-secondary" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" data-bs-target="#deleteCategoryModal"
                                            data-id="<?php echo e($category->id); ?>"
                                            data-name="<?php echo e($category->name); ?>"
                                            title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="text-center">Категории не найдены</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для удаления -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить категорию <strong id="category-name"></strong>?</p>
                <p class="text-danger">Это также удалит все связанные с категорией шаблоны!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form id="delete-category-form" action="" method="POST">
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
    const deleteButtons = document.querySelectorAll('[data-bs-target="#deleteCategoryModal"]');
    const categoryNameEl = document.getElementById('category-name');
    const deleteForm = document.getElementById('delete-category-form');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-id');
            const categoryName = this.getAttribute('data-name');
            
            categoryNameEl.textContent = categoryName;
            deleteForm.action = `<?php echo e(url('admin/template-categories')); ?>/${categoryId}`;
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/admin/template-categories/index.blade.php ENDPATH**/ ?>