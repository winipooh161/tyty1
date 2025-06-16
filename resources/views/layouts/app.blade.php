<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- PWA  -->
    <meta name="theme-color" content="#000000"/>
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Sticap">

    <!-- Принудительно используем mobile-first -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
 
    <!-- Fonts -->
 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">

    <!-- Предзагрузка важных ресурсов -->
    <link rel="preload" href="{{ asset('images/center-icon.svg') }}" as="image">
    <link rel="preload" href="{{ asset('images/icons/person.svg') }}" as="image">
    <link rel="preload" href="{{ asset('images/icons/plus-1.svg') }}" as="image">
    <link rel="preload" href="{{ asset('images/icons/speedometer.svg') }}" as="image">
    <link rel="preload" href="{{ asset('js/qr-scanner.min.js') }}" as="script">

    <!-- Bootstrap CSS и JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Предзагрузка библиотеки QR-сканера -->
    <script>
        // Отложенная загрузка QR-сканера для улучшения производительности
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                // Вначале загружаем worker для QR-сканера
                const workerScript = document.createElement('script');
                workerScript.src = '{{ asset("js/qr-scanner-worker.min.js") }}';
                workerScript.async = true;
                document.head.appendChild(workerScript);
                
                // Затем загружаем основной скрипт
                const script = document.createElement('script');
                script.src = '{{ asset("js/qr-scanner.min.js") }}';
                script.async = true;
                script.onload = function() {
                    if (window.QrScanner) {
                        QrScanner.WORKER_PATH = '{{ asset("js/qr-scanner-worker.min.js") }}';
                        console.log('QR Scanner загружен и настроен');
                        
                        // Проверяем наличие методов в загруженной библиотеке
                        console.log('Методы QR Scanner:', 
                            'hasFlash:', typeof QrScanner.prototype.hasFlash === 'function',
                            'toggleFlash:', typeof QrScanner.prototype.toggleFlash === 'function');
                    }
                };
                document.head.appendChild(script);
            }, 1000); // Загружаем через 1 секунду после загрузки DOM
        });
    </script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/css/mobile-nav.css', 'resources/css/mobile-nav-hint.css', 'resources/css/modal-styles.css', 'resources/js/app.js'])

    <!-- Дополнительные стили и скрипты -->
    @yield('styles')
</head>
<body class="mobile-only-mode">
    <div id="app" class="d-flex">
        @auth
            <!-- Удалена десктопная боковая панель навигации -->
            
            <!-- Подключение мобильной навигации (всегда показывается, скрывается только на странице редактора) -->
            @if(!request()->routeIs('client.templates.editor'))
                @include('layouts.partials.mobile-nav')
                
                <!-- Подключаем конфигурации для всплывающих меню мобильной навигации -->
                @include('layouts.partials.mobile-nav-popup-configs')
            @endif
        @endauth
        
        <main class="py-4 flex-grow-1 content-wrapper {{ request()->routeIs('client.templates.editor') ? 'p-0' : '' }}">
            @yield('content')
        </main>
    </div>
    
    <!-- Axios для AJAX-запросов -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- Скрипт для оптимизации загрузки и анимаций -->
    <script>
        // Предварительно загружаем ресурсы для плавных анимаций
        document.addEventListener('DOMContentLoaded', function() {
            // Добавляем класс для управления анимациями
            document.body.classList.add('animations-ready');
            
            // Функция для проверки поддержки плавных анимаций
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) {
                document.body.classList.add('reduced-motion');
            }
        });
    </script>
    
    <!-- Скрипт для автоматического обновления CSRF токена -->
    <script>
    (function() {
        'use strict';
        
        // Проверяем, не загружен ли уже этот скрипт
        if (window.csrfManagerInitialized) {
            console.warn('CSRF Manager уже инициализирован, пропускаем повторную инициализацию');
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
                            
                            console.log('CSRF токен успешно обновлен');
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
                                
                                // Если это POST, PUT или DELETE запрос, обновляем токен в теле запроса
                                if (['post', 'put', 'patch', 'delete'].includes(config.method.toLowerCase()) && config.data) {
                                    try {
                                        let data = config.data;
                                        
                                        // Если это FormData
                                        if (config.data instanceof FormData) {
                                            // Удаляем старый токен и добавляем новый
                                            config.data.delete('_token');
                                            config.data.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                                        }
                                        // Если это строка (например, сериализованная форма)
                                        else if (typeof config.data === 'string') {
                                            // Заменяем старый токен на новый
                                            let newToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                                            config.data = config.data.replace(/_token=[^&]+/, '_token=' + newToken);
                                        }
                                        // Если это объект JSON
                                        else if (typeof config.data === 'object') {
                                            let data = JSON.parse(config.data);
                                            data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                                            config.data = JSON.stringify(data);
                                        }
                                    } catch (e) {
                                        console.error('Ошибка при обновлении токена в запросе:', e);
                                    }
                                }
                                
                                // Повторяем исходный запрос с обновленным токеном
                                return axios(config);
                            });
                        }
                        
                        // Для других ошибок просто возвращаем их
                        return Promise.reject(error);
                    }
                );
            }
            
            // Устанавливаем обработчики для стандартных fetch-запросов только если не установлены
            if (!window.customFetchSet) {
                window.customFetchSet = true;
                const originalFetch = window.fetch;
                
                window.fetch = function(url, options = {}) {
                    return originalFetch(url, options).then(response => {
                        if (response.status === 419) {
                            // Если ошибка CSRF, обновляем токен и повторяем запрос
                            return refreshCsrfToken().then(token => {
                                // Создаем новые опции с обновленным токеном
                                const newOptions = {...options};
                                
                                // Обновляем заголовки
                                if (!newOptions.headers) {
                                    newOptions.headers = {};
                                }
                                
                                // Обновляем заголовок X-CSRF-TOKEN
                                newOptions.headers['X-CSRF-TOKEN'] = token;
                                
                                // Если это запрос с телом, обновляем токен в теле
                                if (newOptions.body) {
                                    try {
                                        if (newOptions.body instanceof FormData) {
                                            newOptions.body.delete('_token');
                                            newOptions.body.append('_token', token);
                                        }
                                    } catch (e) {
                                        console.error('Ошибка при обновлении токена в fetch-запросе:', e);
                                    }
                                }
                                
                                // Повторяем запрос с обновленным токеном
                                return originalFetch(url, newOptions);
                            });
                        }
                        return response;
                    });
                };
            }
            
            // Запускаем периодическую проверку только если не запущена
            if (!window.csrfRefreshInterval) {
                window.csrfRefreshInterval = setInterval(refreshCsrfToken, 55 * 60 * 1000);
            }
        });
    })();
    </script>
    
    @include('layouts.partials.modal.modal-base')
    @include('layouts.partials.modal.modal-system')
    @include('layouts.partials.modal.modal-qr')
    @include('layouts.partials.modal.modal-profile')
      @include('layouts.partials.modal.modal-share')
        @include('layouts.partials.modal.modal-sub')
    @if(request()->is('client/templates/create-new/*'))
        @include('layouts.partials.modal.modal-template-settings')
    @endif

    @yield('scripts')
</body>
</html>
</body>
</html>
