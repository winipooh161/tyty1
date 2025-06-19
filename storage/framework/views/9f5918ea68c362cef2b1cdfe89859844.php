

<?php $__env->startSection('content'); ?>
<div class="">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white text-center py-4">
                    <h2 class="fw-normal mb-0 text-dark"><?php echo e(__('Вход в систему')); ?></h2>
                </div>
                <div class="card-body p-4 p-md-5">
                   

                    <form method="POST" action="<?php echo e(route('login')); ?>">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label for="phone" class="form-label"><?php echo e(__('Номер телефона')); ?></label>
                            <input id="phone" type="text" class="form-control maskphone <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                name="phone" value="<?php echo e(old('phone')); ?>" required autofocus 
                                placeholder="+7 (___) ___-__-__">

                            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                            <?php elseif(session('login-error')): ?>
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong><?php echo e(session('login-error')); ?></strong>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
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
                                    name="password" required autocomplete="current-password">
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

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="remember">
                                    <?php echo e(__('Запомнить меня')); ?>

                                </label>
                            </div>

                            <?php if(Route::has('password.request')): ?>
                                <a class="text-decoration-none small" href="<?php echo e(route('password.request')); ?>">
                                    <?php echo e(__('Забыли пароль?')); ?>

                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2">
                                <?php echo e(__('Войти')); ?>

                            </button>
                        </div>
                    </form>
                
                    <hr class="my-4">

                    <!-- Вход через социальные сети -->
                    <div class="text-center">
                        <p class="mb-3"><?php echo e(__('Или войдите через')); ?></p>
                        <div class="d-flex justify-content-center gap-3 mb-4">
                            <a href="<?php echo e(route('social.redirect', 'yandex')); ?>" class="btn btn-light border">
                                <i class="bi bi-yandex me-1"></i> <?php echo e(__('Яндекс')); ?>

                            </a>
                            <!-- Можно добавить другие провайдеры -->
                        </div>
                        
                        <p class="mb-0"><?php echo e(__('Еще нет аккаунта?')); ?> 
                            <a href="<?php echo e(route('register')); ?>" class="text-decoration-none fw-bold text-primary">
                                <?php echo e(__('Зарегистрироваться')); ?>

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
            
            // Вызываем маску при загрузке страницы для полей с существующим значением
            if (input.value && input.value.length > 0) {
                mask.call(input);
            }
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
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\tyty\resources\views/auth/login.blade.php ENDPATH**/ ?>