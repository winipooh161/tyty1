

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h3 class="mb-3">Категории шаблонов</h3>
            
            <div class="row g-3">
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="<?php echo e(route('client.templates.index', $category->slug)); ?>" class="text-decoration-none">
                        <div class="card h-100 category-card">
                            <div class="card-body text-center">
                                <?php if($category->icon): ?>
                                    <i class="bi bi-<?php echo e($category->icon); ?> category-icon"></i>
                                <?php else: ?>
                                    <i class="bi bi-grid category-icon"></i>
                                <?php endif; ?>
                                <h5 class="card-title"><?php echo e($category->name); ?></h5>
                                <p class="card-text small text-muted">
                                    <?php echo e($category->template_count); ?> <?php echo e($category->template_count == 1 ? 'шаблон' : 
                                      ($category->template_count >= 2 && $category->template_count <= 4 ? 'шаблона' : 'шаблонов')); ?>

                                </p>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>

<style>
    .category-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 10px;
    }
    
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .category-icon {
        font-size: 2rem;
        margin-bottom: 15px;
        display: block;
        color: #6c757d;
    }
    
    @media (max-width: 576px) {
        .col-6 {
            padding: 0 8px;
        }
        
        .row {
            margin-left: -8px;
            margin-right: -8px;
        }
    }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/templates/categories.blade.php ENDPATH**/ ?>