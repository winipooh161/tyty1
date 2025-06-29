<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <!-- PWA  -->
    <meta name="theme-color" content="#000000" />
    <link rel="manifest" href="<?php echo e(asset('/manifest.json')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset('icons/icon-192x192.png')); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Sticap">

    <!-- Улучшенные мета-теги для мобильных устройств -->
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name', 'Laravel')); ?></title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">

    <!-- Предзагрузка важных ресурсов -->
    <link rel="preload" href="<?php echo e(asset('images/center-icon.svg')); ?>" as="image">
    <link rel="preload" href="<?php echo e(asset('images/icons/person.svg')); ?>" as="image">
    <link rel="preload" href="<?php echo e(asset('images/icons/plus-1.svg')); ?>" as="image">
    <link rel="preload" href="<?php echo e(asset('images/icons/speedometer.svg')); ?>" as="image">
    <link rel="preload" href="<?php echo e(asset('js/qr-scanner.min.js')); ?>" as="script">

    <!-- Bootstrap CSS и JS -->
    <link href="<?php echo e(asset('css/p/bootstrap.min.css')); ?>" rel="stylesheet">
    <script src="<?php echo e(asset('js/p/jquery.min.js')); ?>"></script>
    <script src="<?php echo e(asset('js/p/bootstrap.bundle.min.js')); ?>"></script>


    <!-- Vite Assets -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/css/style.css', 'resources/css/mobile-nav.css', 'resources/css/mobile-nav-hint.css', 'resources/css/modal-styles.css', 'resources/js/app.js']); ?>

    <!-- Дополнительные стили и скрипты -->
    <?php echo $__env->yieldContent('styles'); ?>
    <link href="<?php echo e(asset('css/template-editor.css')); ?>" rel="stylesheet">

    <!-- Дополнительные стили для блокировки системных жестов -->
    <style>
        /* Предотвращение перенаправления при скролле на краях страницы */
        body {
            overscroll-behavior: contain;
        }

        /* Добавление безопасной области внизу для навигации */
        .mb-navigation {
            padding-bottom: env(safe-area-inset-bottom, 10px);
        }

        /* Класс для блокировки скролла при взаимодействии с нижней панелью */
        .touch-action-none {
            touch-action: none;
        }

        /* Дополнительный контейнер для предотвращения жестов */
        .gesture-shield {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 15px;
            z-index: 1050;
            pointer-events: auto;
            touch-action: none;
        }
    </style>
</head>

<body class="mobile-only-mode">
    <!-- Защитный элемент для блокировки системных жестов в нижней части экрана -->
    <div class="gesture-shield" id="gestureShield"></div>

    <div id="app" class="d-flex">
        <?php if(auth()->guard()->check()): ?>
            <!-- Удалена десктопная боковая панель навигации -->

            <!-- Подключение мобильной навигации (всегда показывается, скрывается только на странице редактора) -->

            <?php echo $__env->make('layouts.partials.mobile-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <!-- Подключаем конфигурации для всплывающих меню мобильной навигации -->
            <?php echo $__env->make('layouts.partials.mobile-nav-popup-configs', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <?php endif; ?>

        <main
            class="py-4 flex-grow-1 content-wrapper <?php echo e(request()->routeIs('client.templates.editor') ? 'p-0' : ''); ?>">
            <?php echo $__env->yieldContent('content'); ?>
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
                    return axios.get('<?php echo e(route('refresh-csrf')); ?>')
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
                                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data
                                        .token;
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
                                    if (['post', 'put', 'patch', 'delete'].includes(config.method
                                            .toLowerCase()) && config.data) {
                                        try {
                                            let data = config.data;

                                            // Если это FormData
                                            if (config.data instanceof FormData) {
                                                // Удаляем старый токен и добавляем новый
                                                config.data.delete('_token');
                                                config.data.append('_token', document.querySelector(
                                                    'meta[name="csrf-token"]').getAttribute(
                                                    'content'));
                                            }
                                            // Если это строка (например, сериализованная форма)
                                            else if (typeof config.data === 'string') {
                                                // Заменяем старый токен на новый
                                                let newToken = document.querySelector(
                                                    'meta[name="csrf-token"]').getAttribute(
                                                    'content');
                                                config.data = config.data.replace(/_token=[^&]+/,
                                                    '_token=' + newToken);
                                            }
                                            // Если это объект JSON
                                            else if (typeof config.data === 'object') {
                                                let data = JSON.parse(config.data);
                                                data._token = document.querySelector(
                                                    'meta[name="csrf-token"]').getAttribute(
                                                    'content');
                                                config.data = JSON.stringify(data);
                                            }
                                        } catch (e) {
                                            console.error('Ошибка при обновлении токена в запросе:',
                                                e);
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
                                    const newOptions = {
                                        ...options
                                    };

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
                                            console.error(
                                                'Ошибка при обновлении токена в fetch-запросе:',
                                                e);
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

    <!-- Скрипт для блокировки системных жестов -->
    <script>
        (function() {
            // Инициализируем обработку системных жестов только после загрузки страницы
            document.addEventListener('DOMContentLoaded', function() {
                // Элемент для блокировки системных жестов
                const gestureShield = document.getElementById('gestureShield');

                // Блокируем все жесты на щите
                if (gestureShield) {
                    gestureShield.addEventListener('touchstart', function(e) {
                        e.preventDefault();
                    }, {
                        passive: false
                    });

                    gestureShield.addEventListener('touchmove', function(e) {
                        e.preventDefault();
                    }, {
                        passive: false
                    });
                }

                // Маркер наличия мобильной навигации для других скриптов
                window.hasMobileNav = !!document.querySelector('.mb-navigation');

                // Флаг для отслеживания взаимодействия с нижней панелью
                window.mobileNavInteracting = false;

                // Пользовательское событие для уведомления о взаимодействии с панелью
                window.mobileNavInteractionEvent = new CustomEvent('mobileNavInteraction', {
                    detail: {
                        interacting: false
                    }
                });

                // Добавляем обработчики для предотвращения системных жестов при скролле в нижней части экрана
                document.addEventListener('scroll', function() {
                    const scrollHeight = Math.max(
                        document.body.scrollHeight, document.documentElement.scrollHeight,
                        document.body.offsetHeight, document.documentElement.offsetHeight,
                        document.body.clientHeight, document.documentElement.clientHeight
                    );
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const windowHeight = window.innerHeight;

                    // Если мы близко к низу страницы, активируем защиту
                    if (scrollHeight - scrollTop - windowHeight < 150) {
                        gestureShield.classList.add('active');
                    } else {
                        gestureShield.classList.remove('active');
                    }
                }, {
                    passive: true
                });
            });
        })();
    </script>

    <?php echo $__env->make('layouts.partials.modal.modal-base', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('layouts.partials.modal.modal-system', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('layouts.partials.modal.modal-qr', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('layouts.partials.modal.modal-profile', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('layouts.partials.modal.modal-share', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('layouts.partials.modal.modal-sub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php if(request()->is('client/templates/create-new/*')): ?>
        <?php echo $__env->make('layouts.partials.modal.modal-template-settings', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php echo $__env->yieldContent('scripts'); ?>
    <script src="<?php echo e(asset('js/template-editor.js')); ?>"></script>
</body>

</html>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/app.blade.php ENDPATH**/ ?>