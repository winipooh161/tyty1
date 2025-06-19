/**
 * Общие обработчики для мобильной навигации и модальных окон
 * Включает утилиты для оптимизации работы с модальными окнами и AJAX-запросами
 */
(function() {
    'use strict';
    
    // Проверяем, не загружен ли уже этот скрипт
    if (window.mobileNavHandlersInitialized) {
        console.warn('Обработчики мобильной навигации уже инициализированы');
        return;
    }
    
    window.mobileNavHandlersInitialized = true;
    
    // Инициализация мобильной навигации при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        // Показываем мобильную навигацию сразу после загрузки
        initMobileNavigation();
    });

    // Функция инициализации мобильной навигации
    function initMobileNavigation() {
        const mbNavigation = document.querySelector('.mb-navigation');
        if (mbNavigation) {
            // Добавляем класс для отображения навигации
            mbNavigation.classList.add('mb-nav-loaded');
            
            // Убеждаемся, что навигация не скрыта другими классами
            mbNavigation.classList.remove('mb-nav-hidden');
            
            // Принудительно устанавливаем стили для видимости
            mbNavigation.style.display = 'flex';
            mbNavigation.style.opacity = '1';
            mbNavigation.style.transform = 'translateY(0)';
            
            console.log('Мобильная навигация инициализирована');
            
            // Инициализируем иконки с небольшой задержкой для анимации
            setTimeout(() => {
                const mbIconWrappers = document.querySelectorAll('.mb-icon-wrapper');
                mbIconWrappers.forEach((icon, index) => {
                    icon.style.setProperty('--item-index', index);
                    icon.classList.add('mb-icon-loaded');
                });
            }, 100);
        } else {
            console.warn('Элемент мобильной навигации не найден в DOM');
        }
    }
    
    // Глобальные утилиты
    window.mobileNavUtils = {
        // Функция для блокировки скролла body
        blockBodyScroll: function() {
            const scrollY = window.pageYOffset || document.documentElement.scrollTop;
            
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.width = '100%';
            
            document.body.classList.add('modal-scroll-blocked');
            document.body.dataset.scrollY = scrollY;
        },
        
        // Функция для разблокировки скролла body
        unblockBodyScroll: function() {
            const scrollY = parseInt(document.body.dataset.scrollY || '0', 10);
            
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            
            document.body.classList.remove('modal-scroll-blocked');
            
            window.scrollTo(0, scrollY);
        },
        
        // Оптимизированная функция для AJAX-запросов
        ajaxRequest: function(url, method, data, options = {}) {
            // Настройки по умолчанию
            const settings = {
                showLoading: true,
                handleErrors: true,
                ...options
            };
            
            // Показываем индикатор загрузки
            if (settings.showLoading && window.loadingSpinner) {
                window.loadingSpinner.show();
            }
            
            // Получаем CSRF-токен
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            
            // Формируем параметры запроса
            const fetchOptions = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                credentials: 'same-origin'
            };
            
            // Добавляем тело запроса для методов, отличных от GET
            if (method.toUpperCase() !== 'GET' && data) {
                if (data instanceof FormData) {
                    fetchOptions.body = data;
                } else {
                    fetchOptions.headers['Content-Type'] = 'application/json';
                    fetchOptions.body = JSON.stringify(data);
                }
            }
            
            // Выполняем запрос
            return fetch(url, fetchOptions)
                .then(response => {
                    if (settings.handleErrors && !response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || `Ошибка HTTP: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .finally(() => {
                    // Скрываем индикатор загрузки
                    if (settings.showLoading && window.loadingSpinner) {
                        window.loadingSpinner.hide();
                    }
                });
        },
        
        // Показ уведомлений
        showToast: function(message, type = 'success', duration = 3000) {
            // Проверяем наличие элемента контейнера для уведомлений
            let toastContainer = document.getElementById('toast-container');
            
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container';
                document.body.appendChild(toastContainer);
            }
            
            // Создаем элемент уведомления
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.textContent = message;
            
            // Добавляем уведомление на страницу
            toastContainer.appendChild(toast);
            
            // Показываем уведомление
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Удаляем уведомление через указанное время
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode === toastContainer) {
                        toastContainer.removeChild(toast);
                    }
                }, 300);
            }, duration);
        },
        
        // Функция для копирования текста в буфер обмена
        copyToClipboard: function(text, successCallback) {
            // Используем современный Clipboard API, если доступен
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        if (successCallback) successCallback();
                        else this.showToast('Скопировано в буфер обмена', 'success');
                    })
                    .catch(err => {
                        console.error('Не удалось скопировать: ', err);
                        this.fallbackCopyToClipboard(text, successCallback);
                    });
            } else {
                this.fallbackCopyToClipboard(text, successCallback);
            }
        },
        
        // Резервный метод для копирования в буфер обмена
        fallbackCopyToClipboard: function(text, successCallback) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    if (successCallback) successCallback();
                    else this.showToast('Скопировано в буфер обмена', 'success');
                } else {
                    this.showToast('Не удалось скопировать текст', 'error');
                }
            } catch (err) {
                console.error('Не удалось скопировать текст: ', err);
                this.showToast('Не удалось скопировать текст', 'error');
            }
            
            document.body.removeChild(textArea);
        },
        
        // Маска для телефонных номеров
        initPhoneMask: function(inputElement) {
            if (!inputElement) return;
            
            const maskPhone = function(event) {
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
            };
            
            const setCursorPosition = function(elem, pos) {
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
            };
            
            inputElement.addEventListener("input", maskPhone);
            inputElement.addEventListener("focus", maskPhone);
            inputElement.addEventListener("blur", maskPhone);
            
            // Применяем маску к существующему номеру при загрузке
            if (inputElement.value) {
                maskPhone.call(inputElement, {type: 'input'});
            }
        },
        
        // Функция для повторной инициализации мобильной навигации
        reinitMobileNav: function() {
            // Публичный метод для вызова из других скриптов при необходимости
            initMobileNavigation();
        }
    };
    
    // Добавляем стили для уведомлений, если их нет
    if (!document.getElementById('mobile-nav-handlers-styles')) {
        const style = document.createElement('style');
        style.id = 'mobile-nav-handlers-styles';
        style.textContent = `
            /* Стили для корректного отображения мобильной навигации */
            .mb-navigation {
                display: flex !important;
                opacity: 1 !important;
                transform: translateY(0) !important;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            
            /* Базовые стили для уведомлений */
            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1080;
                max-width: 80%;
            }
            
            .toast-notification {
                padding: 12px 20px;
                background-color: #6c8aec;
                color: white;
                border-radius: 8px;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
                margin-bottom: 10px;
                opacity: 0;
                transform: translateY(-20px);
                transition: opacity 0.3s ease, transform 0.3s ease;
                font-size: 14px;
                font-weight: 500;
            }
            
            .toast-notification.show {
                opacity: 1;
                transform: translateY(0);
            }
            
            .toast-notification.error {
                background-color: #f76b8a;
            }
            
            .toast-notification.success {
                background-color: #28a745;
            }
            
            .toast-notification.warning {
                background-color: #ffc107;
                color: #343a40;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Инициализируем обработку касаний для улучшенного взаимодействия с модальными окнами
    document.addEventListener('DOMContentLoaded', function() {
        // Функция для определения типа устройства пользователя
        function detectMobileDevice() {
            window.isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            document.documentElement.classList.toggle('mobile-device', window.isMobileDevice);
        }
        
        // Обработчик для флага взаимодействия пользователя
        function initUserInteractionTracking() {
            const interactionEvents = ['click', 'touchstart', 'touchmove', 'mousedown', 'keydown'];
            
            const setUserInteracted = () => {
                window.userHasInteractedWithPage = true;
                
                // Удаляем обработчики после первого взаимодействия
                interactionEvents.forEach(event => {
                    document.removeEventListener(event, setUserInteracted, { passive: true });
                });
            };
            
            // Добавляем слушатели событий для определения взаимодействия
            interactionEvents.forEach(event => {
                document.addEventListener(event, setUserInteracted, { passive: true });
            });
        }
        
        // Вызываем функции инициализации
        detectMobileDevice();
        initUserInteractionTracking();
        
        // Повторная проверка наличия навигации через 1 секунду
        setTimeout(() => {
            if (!document.querySelector('.mb-navigation.mb-nav-loaded')) {
                initMobileNavigation();
            }
        }, 1000);
    });
})();
