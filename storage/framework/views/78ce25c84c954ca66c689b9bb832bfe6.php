

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <?php if(session('status')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Профиль</span>
                    <a href="<?php echo e(route('profile.edit')); ?>" class="btn btn-sm btn-primary">Редактировать</a>
                </div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="<?php echo e(Auth::user()->avatar ? asset('storage/avatars/'.Auth::user()->avatar) : asset('images/default-avatar.jpg')); ?>" 
                             class="profile-avatar rounded-circle" alt="Аватар">
                        <h4 class="mt-3"><?php echo e(Auth::user()->name); ?></h4>
                        <p class="text-muted"><?php echo e(Auth::user()->email); ?></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h5>Роль</h5>
                            <p><?php echo e(ucfirst(Auth::user()->role)); ?></p>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <h5>Дата регистрации</h5>
                            <p><?php echo e(Auth::user()->created_at->format('d.m.Y')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/profile/show.blade.php ENDPATH**/ ?>