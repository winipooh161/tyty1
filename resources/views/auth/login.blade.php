@extends('layouts.auth')

@section('content')
<div class="">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white text-center py-4">
                    <h2 class="fw-normal mb-0 text-dark">{{ __('Вход в систему') }}</h2>
                </div>
                <div class="card-body p-4 p-md-5">
                   

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="phone" class="form-label">{{ __('Номер телефона') }}</label>
                            <input id="phone" type="text" class="form-control maskphone @error('phone') is-invalid @enderror" 
                                name="phone" value="{{ old('phone') }}" required autofocus 
                                placeholder="+7 (___) ___-__-__">

                            @error('phone')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @elseif (session('login-error'))
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ session('login-error') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">{{ __('Пароль') }}</label>
                            <div class="input-group">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                    name="password" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>

                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    {{ __('Запомнить меня') }}
                                </label>
                            </div>

                            @if (Route::has('password.request'))
                                <a class="text-decoration-none small" href="{{ route('password.request') }}">
                                    {{ __('Забыли пароль?') }}
                                </a>
                            @endif
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2">
                                {{ __('Войти') }}
                            </button>
                        </div>
                    </form>
                
                    <hr class="my-4">

                    <!-- Вход через социальные сети -->
                    <div class="text-center">
                        <p class="mb-3">{{ __('Или войдите через') }}</p>
                        <div class="d-flex justify-content-center gap-3 mb-4">
                            <a href="{{ route('social.redirect', 'yandex') }}" class="btn btn-light border">
                                <i class="bi bi-yandex me-1"></i> {{ __('Яндекс') }}
                            </a>
                            <!-- Можно добавить другие провайдеры -->
                        </div>
                        
                        <p class="mb-0">{{ __('Еще нет аккаунта?') }} 
                            <a href="{{ route('register') }}" class="text-decoration-none fw-bold text-primary">
                                {{ __('Зарегистрироваться') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
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
@endsection
