

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Категории шаблонов</h2>
            <p class="text-muted">Выберите категорию для просмотра доступных шаблонов</p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col">
            <div class="card h-100 ">
                <?php if($category->image): ?>
                <img src="<?php echo e(asset('storage/category_images/'.$category->image)); ?>" class="card-img-top category-img d-none d-lg-block" alt="<?php echo e($category->name); ?>">
                <?php else: ?>
                <div class="card-img-top category-img-placeholder d-flex align-items-center justify-content-center bg-light d-none d-lg-flex">
                    <i class="bi bi-card-image text-muted" style="font-size: 3rem;"></i>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?php echo e($category->name); ?></h5>
                    <p class="card-text"><?php echo e($category->description); ?></p>
                    
                    <?php if(Auth::user()->isVip()): ?>
                        <!-- VIP пользователи видят все шаблоны -->
                        <a href="<?php echo e(route('client.templates.index', $category->slug)); ?>" class="btn btn-primary">
                            <i class="bi bi-grid me-1"></i> Выбрать шаблон
                        </a>
                        <span class="badge bg-warning text-dark ms-2">VIP</span>
                    <?php else: ?>
                        <!-- Обычные пользователи направляются сразу на стандартный шаблон, если он есть -->
                        <?php
                            $defaultTemplate = \App\Models\Template::where('template_category_id', $category->id)
                                ->where('is_default', true)
                                ->where('is_active', true)
                                ->first();
                        ?>
                        
                        <?php if($defaultTemplate): ?>
                            <a href="<?php echo e(route('client.templates.create-new', $defaultTemplate->id)); ?>" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-1"></i> Создать
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('client.templates.index', $category->slug)); ?>" class="btn btn-secondary disabled">
                                Шаблон недоступен
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/categories.blade.php ENDPATH**/ ?>