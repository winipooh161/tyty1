

<?php $__env->startSection('content'); ?>
<div class="">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white text-center py-4">
                    <h2 class="fw-normal mb-0 text-dark"><?php echo e(__('Регистрация')); ?></h2>
                </div>
                <div class="card-body p-4 p-md-5">
                   
                    <form method="POST" action="<?php echo e(route('register')); ?>">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label for="name" class="form-label"><?php echo e(__('Имя')); ?></label>
                            <input id="name" type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                name="name" value="<?php echo e(old('name')); ?>" required autocomplete="name" autofocus>

                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label"><?php echo e(__('Номер телефона')); ?> <span class="text-danger">*</span></label>
                            <input id="phone" type="text" class="form-control maskphone <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                name="phone" value="<?php echo e(old('phone')); ?>" required autocomplete="tel"
                                placeholder="+7 (___) ___-__-__">
                           
                            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo e(__('Email')); ?> <span class="text-muted">(необязательно)</span></label>
                            <input id="email" type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                name="email" value="<?php echo e(old('email')); ?>" autocomplete="email"
                                <?php if(empty(old('email'))): ?> data-no-email="1" <?php endif; ?>>
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo e(__('Пароль')); ?></label>
                            <div class="input-group">
                                <input id="password" type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                    name="password" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-4">
                            <label for="password-confirm" class="form-label"><?php echo e(__('Подтверждение пароля')); ?></label>
                            <div class="input-group">
                                <input id="password-confirm" type="password" class="form-control" 
                                    name="password_confirmation" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                    <i class="bi bi-eye" id="togglePasswordConfirmIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2">
                                <?php echo e(__('Зарегистрироваться')); ?>

                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <!-- Регистрация через социальные сети -->
                    <div class="text-center">
                        <p class="mb-3"><?php echo e(__('Или зарегистрируйтесь через')); ?></p>
                        <div class="d-flex justify-content-center gap-3 mb-4">
                            <a href="<?php echo e(route('social.redirect', 'yandex')); ?>" class="btn btn-light border">
                                <i class="bi bi-yandex me-1"></i> <?php echo e(__('Яндекс')); ?>

                            </a>
                            <!-- Можно добавить другие провайдеры -->
                        </div>
                        
                        <p class="mb-0"><?php echo e(__('Уже есть аккаунт?')); ?> 
                            <a href="<?php echo e(route('login')); ?>" class="text-decoration-none fw-bold text-primary">
                                <?php echo e(__('Войти')); ?>

                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    // Маска для ввода телефона
    document.addEventListener("DOMContentLoaded", function () {
        var inputs = document.querySelectorAll("input.maskphone");
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            input.addEventListener("input", mask);
            input.addEventListener("focus", mask);
            input.addEventListener("blur", mask);
        }
        function mask(event) {
            var blank = "+_ (___) ___-__-__";
            var i = 0;
            var val = this.value.replace(/\D/g, "").replace(/^8/, "7").replace(/^9/, "79");
            this.value = blank.replace(/./g, function (char) {
                if (/[_\d]/.test(char) && i < val.length) return val.charAt(i++);
                return i >= val.length ? "" : char;
            });
            if (event.type == "blur") {
                if (this.value.length == 2) this.value = "";
            } else {
                setCursorPosition(this, this.value.length);
            }
        }
        
        function setCursorPosition(elem, pos) {
            elem.focus();
            if (elem.setSelectionRange) {
                elem.setSelectionRange(pos, pos);
                return;
            }
            if (elem.createTextRange) {
                var range = elem.createTextRange();
                range.collapse(true);
                range.moveEnd("character", pos);
                range.moveStart("character", pos);
                range.select();
                return;
            }
        }
        
        // Переключение видимости пароля
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Переключение видимости подтверждения пароля
        document.getElementById('togglePasswordConfirm').addEventListener('click', function() {
            const passwordInput = document.getElementById('password-confirm');
            const icon = document.getElementById('togglePasswordConfirmIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Удаляем name="email" если поле пустое перед отправкой формы
        const regForm = document.querySelector('form[action="<?php echo e(route('register')); ?>"]');
        if (regForm) {
            regForm.addEventListener('submit', function(e) {
                const emailInput = regForm.querySelector('input#email');
                if (emailInput && !emailInput.value) {
                    emailInput.removeAttribute('name');
                }
            });
        }
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/auth/register.blade.php ENDPATH**/ ?>