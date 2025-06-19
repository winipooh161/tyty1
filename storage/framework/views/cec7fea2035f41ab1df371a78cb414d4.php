

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Информационная страница</div>

                <div class="card-body">
                    <?php if(auth()->guard()->check()): ?>
                        <?php if(Auth::user()->role === 'admin'): ?>
                            <div class="alert alert-info">
                                Вы просматриваете эту страницу как администратор.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <p>Эта страница доступна всем посетителям.</p>
                    <p>Зарегистрируйтесь, чтобы получить доступ к дополнительным функциям.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/user/dashboard.blade.php ENDPATH**/ ?>