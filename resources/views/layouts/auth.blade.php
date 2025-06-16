<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
 
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Bootstrap CSS и JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Дополнительные стили для страниц аутентификации -->
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .auth-container {
            flex-grow: 1;
            display: flex;
            align-items: center;
        }
        .auth-card {
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .auth-header {
            border-radius: 1rem 1rem 0 0;
            padding: 1.5rem;
        }
    </style>
    
    @yield('styles')
</head>
<body>
    <div id="app">
        <header class="shadow-sm bg-white py-2">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <a class="navbar-brand" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}" height="40">
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="auth-container py-4">
            @yield('content')
        </main>
        
        <footer class="bg-white py-3 border-top">
            <div class="container">
                <div class="text-center text-muted">
                    © {{ date('Y') }} {{ config('app.name') }}
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Axios для AJAX-запросов -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- Скрипт для автоматического обновления CSRF токена -->
    <script>
    (function() {
        'use strict';
        
        // Проверяем, не загружен ли уже этот скрипт
        if (window.csrfManagerInitialized) {
            return;
        }
        
        window.csrfManagerInitialized = true;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Функция для обновления CSRF токена
            function refreshCsrfToken() {
                return axios.get('{{ route('refresh-csrf') }}')
                    .then(function(response) {
                        if (response.data && response.data.token) {
                            // Обновляем токен в мета-теге
                            const tokenElement = document.querySelector('meta[name="csrf-token"]');
                            if (tokenElement) {
                                tokenElement.setAttribute('content', response.data.token);
                            }
                            
                            // Обновляем токен во всех формах
                            document.querySelectorAll('input[name="_token"]').forEach(input => {
                                input.value = response.data.token;
                            });
                            
                            // Обновляем заголовок для Axios
                            if (window.axios) {
                                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.token;
                            }
                            
                            return response.data.token;
                        }
                    })
                    .catch(function(error) {
                        console.error('Не удалось обновить CSRF токен:', error);
                    });
            }
            
            // Настраиваем перехватчик для Axios только если он еще не настроен
            if (window.axios && !window.axios.csrfInterceptorSet) {
                window.axios.csrfInterceptorSet = true;
                
                axios.interceptors.response.use(
                    response => response,
                    error => {
                        // Определяем ошибку CSRF токена
                        const isCsrfError = error.response && 
                            (error.response.status === 419 || 
                            (error.response.status === 422 && error.response.data.message && 
                             error.response.data.message.includes('CSRF')));
                        
                        if (isCsrfError) {
                            // Если это ошибка CSRF, обновляем токен и повторяем запрос
                            return refreshCsrfToken().then(() => {
                                // Создаем новый экземпляр запроса с обновленным токеном
                                const config = error.config;
                                
                                // Обновляем токен в запросе
                                if (['post', 'put', 'patch', 'delete'].includes(config.method.toLowerCase()) && config.data) {
                                    try {
                                        if (config.data instanceof FormData) {
                                            config.data.delete('_token');
                                            config.data.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                                        }
                                        else if (typeof config.data === 'string') {
                                            let newToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                                            config.data = config.data.replace(/_token=[^&]+/, '_token=' + newToken);
                                        }
                                        else if (typeof config.data === 'object') {
                                            let data = JSON.parse(config.data);
                                            data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                                            config.data = JSON.stringify(data);
                                        }
                                    } catch (e) {
                                        console.error('Ошибка при обновлении токена в запросе:', e);
                                    }
                                }
                                
                                return axios(config);
                            });
                        }
                        
                        return Promise.reject(error);
                    }
                );
            }
            
            if (!window.csrfRefreshInterval) {
                window.csrfRefreshInterval = setInterval(refreshCsrfToken, 55 * 60 * 1000);
            }
        });
    })();
    </script>

    @yield('scripts')
</body>
</html>
