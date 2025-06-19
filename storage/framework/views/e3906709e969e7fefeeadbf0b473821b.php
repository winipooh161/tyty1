<!-- filepath: c:\ospanel\domains\tyty\resources\views\layouts\partials\modal\qrScannerModal.blade.php -->
<!-- Модальная система для мобильной навигации -->
<div class="modal-panel-container">
    <!-- QR сканер модальное окно -->
    <div class="modal-panel" id="qr-scanner-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-panel-dialog modal-fullscreen">
            <div class="modal-panel-content">
                <div class="modal-panel-header">
                    <h5 class="modal-panel-title">QR сканер</h5>
                    <button type="button" class="modal-panel-close" data-modal-close aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-panel-body p-0">
                    <div class="camera-container">
                        <video id="qrScannerVideo" playsinline></video>
                        <div class="scanner-overlay">
                            <div class="scanner-frame"></div>
                        </div>
                        <div class="scanning-status p-3 text-center">
                            <div id="scannerStatus" class="mb-2">Подготовка камеры...</div>
                            <div id="scannerResult" class="mt-2 fw-bold"></div>
                        </div>
                    </div>
                </div>
               
            </div>
        </div>
    </div>
    
    <!-- Модальное окно профиля пользователя -->
    <div class="modal-panel fade" id="user-profile-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-panel-dialog">
            <div class="modal-panel-content">
                <div class="modal-panel-header">
                    <h5 class="modal-panel-title">Профиль пользователя</h5>
                    <button type="button" class="modal-panel-close" onclick="closeModalPanel('user-profile-modal')">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="modal-panel-body">
                    <div class="text-center mb-4">
                        <div class="avatar-upload-container position-relative mx-auto" style="width: 150px; height: 150px;">
                            <img id="profile-avatar-preview" 
                                src="<?php echo e(Auth::user()->avatar ? asset('storage/avatars/'.Auth::user()->avatar) : asset('images/default-avatar.jpg')); ?>" 
                                class="profile-avatar rounded-circle w-100 h-100" 
                                alt="Аватар пользователя"
                                style="object-fit: cover;">
                                
                            <div class="avatar-overlay rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-camera"></i>
                                <input type="file" id="avatar-upload" class="position-absolute opacity-0 w-100 h-100 top-0 left-0" 
                                    style="cursor: pointer;" accept="image/*">
                            </div>
                        </div>
                        
                        <h4 class="mt-3"><?php echo e(Auth::user()->name); ?></h4>
                        <p class="text-muted mb-1"><?php echo e(Auth::user()->email); ?></p>
                        
                        <!-- Информация о балансе SUP если есть -->
                        <?php
                            $supBalance = Auth::user()->supBalance ? Auth::user()->supBalance->amount : 0;
                        ?>
                        <p class="badge bg-primary">Баланс SUP: <?php echo e($supBalance); ?></p>
                    </div>
                    
                    <!-- Форма обновления профиля -->
                    <form id="profile-update-form" action="<?php echo e(route('user.update-profile')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        
                        <div class="mb-3">
                            <label for="profile-name" class="form-label">Имя</label>
                            <input type="text" class="form-control" id="profile-name" 
                                name="name" value="<?php echo e(Auth::user()->name); ?>">
                        </div>
                        
                        <div id="avatar-update-form" class="d-none">
                            <input type="hidden" name="avatar_updated" value="0">
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" onclick="closeModalPanel('user-profile-modal')">
                                Отмена
                            </button>
                            <button type="submit" class="btn btn-primary" id="save-profile-btn">
                                Сохранить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Шаблон для добавления других модальных окон -->
    <!-- 
    <div class="modal-panel" id="your-modal-id" tabindex="-1" aria-hidden="true">
        <div class="modal-panel-dialog">
            <div class="modal-panel-content">
                <div class="modal-panel-header">
                    <h5 class="modal-panel-title">Заголовок</h5>
                    <button type="button" class="modal-panel-close" data-modal-close aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-panel-body">
                    Содержимое окна
                </div>
                <div class="modal-panel-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Закрыть</button>
                </div>
            </div>
        </div>
    </div>
    -->

    <!-- Затемнение фона для модальных окон -->
    <div class="modal-backdrop" id="modal-backdrop"></div>
</div>

<!-- Стили для модальной системы -->
<style>
.modal-panel {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1050;
    display: none;
    overflow: hidden;
    outline: 0;
}

.modal-panel.show {
    display: flex !important;
    align-items: flex-end;
}

.modal-panel-dialog {
    position: relative;
    width: 100%;
    max-height: 90vh;
    margin: 0 auto;
    pointer-events: none;
    transform: translateY(100%);
    transition: transform 0.3s ease-out;
    max-width: 500px;
}

.modal-fullscreen .modal-panel-dialog {
    max-width: 100%;
    height: 100%;
    max-height: 100vh;
}

.modal-panel.show .modal-panel-dialog {
    transform: translateY(0);
}

.modal-panel-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    pointer-events: auto;
    background-color: #fff;
    background-clip: padding-box;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.15);
    outline: 0;
    overflow: hidden;
}

.modal-fullscreen .modal-panel-content {
    border-radius: 0;
    height: 100%;
}

.modal-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-panel-title {
    margin: 0;
    font-weight: 600;
    font-size: 1.1rem;
}

.modal-panel-close {
    background: transparent;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 5px;
    margin: -5px;
    color: #6c757d;
}

.modal-panel-body {
    position: relative;
    flex: 1 1 auto;
    padding: 20px;
    overflow-y: auto;
}

.modal-panel-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 15px 20px;
    border-top: 1px solid #e9ecef;
    gap: 10px;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: none;
}

.modal-backdrop.show {
    opacity: 1;
    display: block;
}

/* Анимация входа */
.modal-panel.animate-in .modal-panel-dialog {
    animation: modalSlideIn 0.3s forwards;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

/* Анимация выхода */
.modal-panel.animate-out .modal-panel-dialog {
    animation: modalSlideOut 0.3s forwards;
}

@keyframes modalSlideOut {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(100%);
    }
}

/* Стили для модального окна профиля */
.profile-avatar-container {
    position: relative;
    border-radius: 50%;
    overflow: hidden;
}

.profile-avatar-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    transition: all 0.3s;
}

.profile-avatar-preview {
    transition: all 0.3s;
}

.profile-avatar-container:hover .profile-avatar-preview {
    filter: brightness(0.8);
}

/* Стили для карточки баланса SUP */
.sup-balance-card {
    background: linear-gradient(145deg, #f6f9fc, #ffffff);
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
}
</style>

<!-- Встроенная минимальная версия QR-сканера для обеспечения его доступности -->
<script>
// Встроенная мини-версия QR сканера для случая, если внешний скрипт не загрузится
if (typeof QrScanner === 'undefined') {
    class MinimalQrScanner {
        constructor(videoElem, onResult, options = {}) {
            this.videoElem = videoElem;
            this.onResult = onResult;
            this.options = options;
            this.active = false;
        }
        
        static hasCamera() {
            return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        }
        
        static async listCameras() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) 
                return [];
            
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                return devices.filter(d => d.kind === 'videoinput');
            } catch (e) {
                console.error('Ошибка при получении списка камер:', e);
                return [];
            }
        }
        
        async start() {
            if (this.active) return;
            
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                this.videoElem.srcObject = this.stream;
                this.videoElem.play();
                this.active = true;
                
                // Для демонстрации - в реальной версии здесь был бы код сканирования
                console.log('MinimalQrScanner: Camera started');
            } catch (e) {
                console.error('MinimalQrScanner: Ошибка запуска камеры', e);
                throw e;
            }
        }
        
        stop() {
            if (!this.active) return;
            
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.videoElem.srcObject = null;
            this.active = false;
        }
        
        destroy() {
            this.stop();
        }
    }
    
    window.QrScanner = MinimalQrScanner;
    console.warn('Используется минимальная версия QR сканера. Для полной функциональности загрузите библиотеку.');
}
</script>

<!-- JavaScript для работы с модальными окнами -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Предварительно загружаем библиотеку QR-сканера, чтобы она была доступна при открытии модального окна
    (function loadQrScannerLib() {
        if (typeof QrScanner !== 'undefined' && QrScanner.constructor.name !== 'MinimalQrScanner') {
            console.log('QR-сканер уже загружен');
            return;
        }
        
        const script = document.createElement('script');
        script.src = '/js/qr-scanner.min.js';
        script.async = true;
        script.onload = function() {
            console.log('Библиотека QR-сканера успешно загружена');
            // После загрузки основной библиотеки, загружаем worker
            if (QrScanner && QrScanner.WORKER_PATH === undefined) {
                QrScanner.WORKER_PATH = '/js/qr-scanner-worker.min.js';
            }
        };
        script.onerror = function() {
            console.error('Не удалось загрузить библиотеку QR-сканера');
        };
        document.head.appendChild(script);
    })();
    
    /**
     * Класс для управления системой модальных окон
     */
    class ModalPanelSystem {
        constructor() {
            this.activeModal = null;
            this.backdrop = document.getElementById('modal-backdrop');
            this.init();
            
            // QR сканер
            this.qrScanner = null;
            this.scannerInitialized = false;
        }
        
        init() {
            // Инициализация обработчиков событий для кнопок открытия/закрытия
            this.setupEventListeners();
        }
        
        setupEventListeners() {
            // Обработчики для закрытия модальных окон
            document.querySelectorAll('[data-modal-close]').forEach(button => {
                button.addEventListener('click', () => this.closeModal());
            });
            
            // Закрытие при клике на фон (если не запрещено атрибутом)
            this.backdrop.addEventListener('click', () => {
                if (this.activeModal && !this.activeModal.hasAttribute('data-static')) {
                    this.closeModal();
                }
            });
            
            // Обработка нажатия Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.activeModal) {
                    this.closeModal();
                }
            });
        }
        
        /**
         * Открыть модальное окно по ID
         */
        openModal(modalId) {
            // Активно блокируем показ глобального спиннера перед открытием модального окна
            this.blockLoadingSpinner();
            
            // Если уже открыто другое модальное окно, закрываем его
            if (this.activeModal) {
                this.closeModal(true);
            }
            
            const modal = document.getElementById(modalId);
            if (!modal) return false;
            
            // Показываем фон и модальное окно
            this.backdrop.classList.add('show');
            modal.classList.add('show', 'animate-in');
            modal.style.display = 'flex';
            
            // Блокируем прокрутку страницы
            document.body.style.overflow = 'hidden';
            
            // Обновляем активное модальное окно
            this.activeModal = modal;
            
            // Вибрация для обратной связи (если поддерживается)
            if (navigator.vibrate && window.userHasInteractedWithPage && 
                !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                try {
                    navigator.vibrate(30);
                } catch (error) {
                    // Игнорируем ошибки vibrate API
                }
            }
            
            // Инициализируем QR сканер, если это соответствующее модальное окно
            if (modalId === 'qr-scanner-modal') {
                this.initQrScanner();
            }
            
            return true;
        }
        
        // Добавляем новый метод для активного блокирования спиннера
        blockLoadingSpinner() {
            if (!window.loadingSpinner) return;
            
            // Немедленно скрываем спиннер, если он показан
            window.loadingSpinner.forceHide();
            
            // Блокируем метод show на короткое время
            const originalShow = window.loadingSpinner.show;
            window.loadingSpinner.show = function() { 
                console.log('LoadingSpinner.show заблокирован для модального окна');
            };
            
            // Восстанавливаем метод show через небольшую задержку
            setTimeout(() => {
                window.loadingSpinner.show = originalShow;
            }, 1000);
            
            // Добавляем класс для маркировки текущего состояния
            document.body.classList.add('modal-active');
            
            // Удаляем маркер по закрытию модального окна
            setTimeout(() => {
                document.body.classList.remove('modal-active');
            }, 1000);
        }
        
        /**
         * Закрыть активное модальное окно
         */
        closeModal(immediate = false) {
            if (!this.activeModal) return;
            
            // Остановка QR сканера, если был активен
            if (this.activeModal.id === 'qr-scanner-modal' && this.qrScanner) {
                this.stopQrScanner();
            }
            
            if (immediate) {
                // Немедленное закрытие без анимации
                this.backdrop.classList.remove('show');
                this.activeModal.classList.remove('show', 'animate-in');
                this.activeModal.style.display = 'none';
                document.body.style.overflow = '';
                this.activeModal = null;
            } else {
                // Закрытие с анимацией
                this.backdrop.classList.remove('show');
                this.activeModal.classList.remove('animate-in');
                this.activeModal.classList.add('animate-out');
                
                // Ждем завершения анимации
                setTimeout(() => {
                    this.activeModal.classList.remove('show', 'animate-out');
                    this.activeModal.style.display = 'none';
                    document.body.style.overflow = '';
                    this.activeModal = null;
                }, 300);
            }
        }
        
        /**
         * Инициализация QR сканера
         */
        async initQrScanner() {
            if (this.scannerInitialized) return;
            
            try {
                const statusElement = document.getElementById('scannerStatus');
                const resultElement = document.getElementById('scannerResult');
                
                // Проверка на наличие элемента видео
                const video = document.getElementById('qrScannerVideo');
                if (!video) {
                    throw new Error('Элемент видео не найден');
                }
                
                // Очищаем предыдущие сообщения
                statusElement.textContent = 'Запрос доступа к камере...';
                resultElement.textContent = '';
                
                // Проверка наличия QrScanner и, если нужно, его загрузка
                if (typeof QrScanner === 'undefined' || QrScanner.constructor.name === 'MinimalQrScanner') {
                    statusElement.textContent = 'Загрузка библиотеки QR-сканера...';
                    try {
                        await this.loadQrScannerLibrary();
                        statusElement.textContent = 'Библиотека загружена, получаем доступ к камере...';
                    } catch(e) {
                        console.error('Ошибка загрузки библиотеки:', e);
                        statusElement.textContent = 'Ошибка загрузки библиотеки сканера';
                        throw new Error('Не удалось загрузить библиотеку QR-сканера');
                    }
                }
                
                // Проверка доступности QrScanner после загрузки
                if (typeof QrScanner === 'undefined') {
                    throw new Error('Библиотека QR-сканера недоступна');
                }
                
                // Настройка пути к worker-скрипту (если еще не установлен)
                if (QrScanner.WORKER_PATH === undefined) {
                    QrScanner.WORKER_PATH = '/js/qr-scanner-worker.min.js';
                }
                
                // Проверка доступности камеры
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Ваше устройство не поддерживает доступ к камере');
                }
                
                // Создаем экземпляр QR-сканера с обработкой ошибок
                this.qrScanner = new QrScanner(
                    video,
                    result => {
                        // Обработка успешного сканирования
                        statusElement.textContent = 'QR-код найден!';
                        resultElement.textContent = result.data;
                        
                        // Вибрация при успешном сканировании
                        if (navigator.vibrate) {
                            navigator.vibrate([100, 50, 100]);
                        }
                        
                        // Останавливаем сканирование
                        this.qrScanner.stop();
                        
                        // Перенаправление по ссылке через 1.5 секунды
                        setTimeout(() => {
                            // Проверяем, является ли результат ссылкой
                            if (result.data.startsWith('http')) {
                                window.location.href = result.data;
                            } else {
                                // Если не ссылка, просто показываем результат
                                statusElement.textContent = 'Сканирование завершено';
                            }
                        }, 1500);
                    },
                    error => {
                        // Обработка ошибок сканирования
                        console.error('QR сканер: ошибка сканирования', error);
                        statusElement.textContent = 'Ошибка сканирования';
                    }
                );
                
                try {
                    // Безопасно проверяем наличие камер, обрабатывая возможные ошибки
                    let cameras = [];
                    try {
                        cameras = await QrScanner.listCameras(true);
                    } catch (e) {
                        console.warn('Ошибка при получении списка камер:', e);
                    }
                    
                    if (cameras.length === 0) {
                        console.warn('Камеры не обнаружены, но всё равно попробуем запустить');
                    }
                    
                    statusElement.textContent = 'Запуск камеры...';
                    await this.qrScanner.start();
                    statusElement.textContent = 'Наведите камеру на QR-код';
                    this.scannerInitialized = true;
                    
                    // Показываем кнопку переключения камеры, если доступно более одной камеры
                    const switchBtn = document.getElementById('switchCameraBtn');
                    if (switchBtn && cameras.length > 1) {
                        switchBtn.style.display = 'block';
                        switchBtn.onclick = () => this.switchCamera();
                    }
                } catch (startError) {
                    // Более подробный вывод ошибки
                    console.error('Ошибка при запуске QR сканера:', startError);
                    statusElement.textContent = 'Не удалось запустить камеру';
                    resultElement.innerHTML = `
                        <div class="alert alert-warning">
                            ${startError.message || 'Убедитесь, что вы дали разрешение на использование камеры'}
                        </div>
                        <button class="btn btn-sm btn-primary mt-2" id="retryCameraBtn">Повторить</button>
                    `;
                    
                    document.getElementById('retryCameraBtn')?.addEventListener('click', () => {
                        resultElement.innerHTML = '';
                        this.stopQrScanner();
                        this.scannerInitialized = false;
                        setTimeout(() => this.initQrScanner(), 500);
                    });
                }
                
            } catch (error) {
                console.error('Ошибка при инициализации QR сканера:', error);
                
                const statusElement = document.getElementById('scannerStatus');
                const resultElement = document.getElementById('scannerResult');
                
                statusElement.textContent = 'Ошибка доступа к камере';
                resultElement.innerHTML = `
                    <div class="alert alert-danger">
                        ${error.message || 'Проверьте, что у вас есть камера и вы дали разрешение на её использование'}
                    </div>
                    <button class="btn btn-sm btn-primary mt-2" id="retryCamera">Повторить</button>
                `;
                
                document.getElementById('retryCamera')?.addEventListener('click', () => {
                    resultElement.innerHTML = '';
                    this.stopQrScanner();
                    this.scannerInitialized = false;
                    setTimeout(() => this.initQrScanner(), 500);
                });
            }
        }
        
        /**
         * Переключение между камерами устройства
         */
        async switchCamera() {
            if (!this.qrScanner) return;
            
            try {
                await this.qrScanner.stop();
                // Если метод hasCamera определен (в полной версии библиотеки)
                if (typeof this.qrScanner.hasCamera === 'function') {
                    this.qrScanner.setCamera('environment');
                } else {
                    // Фолбек для минимальной версии
                    await this.qrScanner.start();
                }
                document.getElementById('scannerStatus').textContent = 'Камера переключена';
            } catch (e) {
                console.error('Ошибка переключения камеры:', e);
                document.getElementById('scannerStatus').textContent = 'Ошибка при переключении камеры';
            }
        }
        
        /**
         * Остановка QR сканера с улучшенной обработкой ошибок
         */
        stopQrScanner() {
            if (this.qrScanner) {
                try {
                    this.qrScanner.stop();
                    this.qrScanner.destroy();
                } catch (e) {
                    console.warn('Ошибка при остановке QR сканера:', e);
                }
                this.qrScanner = null;
            }
            this.scannerInitialized = false;
        }
        
        /**
         * Улучшенная загрузка библиотеки QR-сканера с обработкой ошибок и таймаутом
         */
        loadQrScannerLibrary() {
            return new Promise((resolve, reject) => {
                // Если библиотека уже загружена, сразу резолвим
                if (typeof QrScanner !== 'undefined' && QrScanner.constructor.name !== 'MinimalQrScanner') {
                    resolve();
                    return;
                }
                
                let timeout = setTimeout(() => {
                    reject(new Error('Время ожидания загрузки библиотеки истекло'));
                }, 10000);
                
                const script = document.createElement('script');
                script.src = '/js/qr-scanner.min.js';
                script.onload = () => {
                    clearTimeout(timeout);
                    
                    // После загрузки основного скрипта, устанавливаем путь к worker
                    if (QrScanner && QrScanner.WORKER_PATH === undefined) {
                        QrScanner.WORKER_PATH = '/js/qr-scanner-worker.min.js';
                    }
                    
                    resolve();
                };
                script.onerror = () => {
                    clearTimeout(timeout);
                    reject(new Error('Не удалось загрузить библиотеку QR сканера'));
                };
                document.head.appendChild(script);
            });
        }
    }
    
    // Создаем глобальный экземпляр системы модальных окон
    window.modalPanel = new ModalPanelSystem();
    
    // Универсальная функция для открытия модальных окон
    window.openModalPanel = function(modalId) {
        if (window.modalPanel) {
            return window.modalPanel.openModal(modalId);
        }
        return false;
    };
    
    // Связываем функцию открытия QR-сканера с существующей функцией
    window.openQrScannerModal = function() {
        return openModalPanel('qr-scanner-modal');
    };
    
    // Добавляем прямую обработку иконки QR-сканера и других модальных элементов
    document.querySelectorAll('.mb-icon-wrapper[data-modal="true"]').forEach(icon => {
        // Добавляем класс для CSS-селектора
        icon.classList.add('modal-trigger');
        
        const linkElement = icon.querySelector('a');
        if (linkElement) {
            // Добавляем класс для CSS-селектора
            linkElement.classList.add('no-spinner');
            
            // Только если нет атрибута onclick
            if (!linkElement.getAttribute('onclick')) {
                linkElement.addEventListener('click', function(e) {
                    // Предотвращаем стандартное поведение и всплытие события
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Принудительно скрываем спиннер, если он активен
                    if (window.loadingSpinner) {
                        window.loadingSpinner.forceHide();
                    }
                    
                    // Блокируем показ спиннера для всех действий в течение короткого времени
                    if (window.loadingSpinner && window.loadingSpinner.blockShowTemporarily) {
                        window.loadingSpinner.blockShowTemporarily();
                    }
                    
                    // Открываем модальное окно
                    const modalId = icon.getAttribute('data-modal-target');
                    if (modalId && window.modalPanel) {
                        // Небольшая задержка для гарантированного выполнения после обработчиков спиннера
                        setTimeout(() => {
                            window.modalPanel.openModal(modalId);
                        }, 10);
                    }
                });
            }
        }
    });
    
    // Код для работы с модальным окном профиля
    const profileModal = document.getElementById('user-profile-modal');
    if (profileModal) {
        // Загружаем баланс SUP при открытии модального окна
        profileModal.addEventListener('show.modal-panel', function() {
            loadSupBalance();
        });
        
        // Обработчик загрузки аватара
        const avatarUpload = document.getElementById('avatar-upload');
        if (avatarUpload) {
            avatarUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    uploadAvatar(this.files[0]);
                }
            });
        }
        
        // Обработчик сохранения изменений профиля
        const saveButton = document.getElementById('save-profile-changes');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                saveProfileChanges();
            });
        }
    }
    
    // Функция для загрузки баланса SUP
    function loadSupBalance() {
        const balanceElement = document.getElementById('user-sup-balance');
        
        fetch('/sup/balance')
            .then(response => response.json())
            .then(data => {
                balanceElement.textContent = data.formatted_balance + ' SUP';
            })
            .catch(error => {
                console.error('Ошибка при загрузке баланса SUP:', error);
                balanceElement.textContent = 'Ошибка загрузки';
            });
    }
    
    // Функция для загрузки нового аватара
    function uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        // Показываем индикатор загрузки
        const avatarPreview = document.querySelector('.profile-avatar-preview');
        avatarPreview.style.opacity = '0.5';
        
        fetch('/user/update-avatar', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Обновляем изображение аватара
                avatarPreview.src = data.avatar_url;
                avatarPreview.style.opacity = '1';
                
                // Обновляем все аватары пользователя на странице
                document.querySelectorAll('.user-avatar-image').forEach(img => {
                    img.src = data.avatar_url;
                });
                
                showToast('Аватар успешно обновлен');
            } else {
                showToast('Ошибка при обновлении аватара', 'error');
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке аватара:', error);
            avatarPreview.style.opacity = '1';
            showToast('Ошибка при загрузке аватара', 'error');
        });
    }
    
    // Функция для сохранения изменений профиля
    function saveProfileChanges() {
        const nameInput = document.getElementById('edit-name');
        const emailInput = document.getElementById('edit-email');
        const nameError = document.getElementById('name-error');
        const emailError = document.getElementById('email-error');
        
        // Сбрасываем ошибки
        nameInput.classList.remove('is-invalid');
        emailInput.classList.remove('is-invalid');
        nameError.textContent = '';
        emailError.textContent = '';
        
        // Получаем данные формы
        const formData = {
            name: nameInput.value,
            email: emailInput.value,
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
        
        // Отправляем запрос на сервер
        fetch('/user/update-profile', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Обновляем отображаемое имя и email
                document.querySelector('.user-name').textContent = data.user.name;
                document.querySelector('.user-email').textContent = data.user.email;
                
                // Показываем короткое уведомление об успехе
                const toast = document.createElement('div');
                toast.className = 'toast-notification';
                toast.textContent = 'Профиль успешно обновлен!';
                document.body.appendChild(toast);
                
                // Закрываем модальное окно
                closeModalPanel('user-profile-modal');
                
                // Перенаправляем на страницу шаблонов пользователя после короткой задержки
                setTimeout(() => {
                    window.location.href = '/client/my-templates';
                }, 300);
            } else {
                // Показываем ошибку
                const toast = document.createElement('div');
                toast.className = 'toast-notification error';
                toast.textContent = data.message || 'Произошла ошибка при обновлении профиля';
                document.body.appendChild(toast);
                
                // Удаляем уведомление через 3 секунды
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Ошибка при обновлении профиля:', error);
            // Показываем ошибку
            const toast = document.createElement('div');
            toast.className = 'toast-notification error';
            toast.textContent = 'Произошла ошибка при обновлении профиля';
            document.body.appendChild(toast);
            
            // Удаляем уведомление через 3 секунды
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        });
    }
    
    // Функция для отображения уведомлений
    function showToast(message, type = 'success') {
        // Создаем элемент уведомления
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.textContent = message;
        
        // Добавляем уведомление на страницу
        document.body.appendChild(toast);
        
        // Удаляем уведомление через 3 секунды
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
});

// Расширяем существующий класс ModalPanelSystem
document.addEventListener('DOMContentLoaded', function() {
    // Добавляем новую функциональность к существующему ModalPanelSystem
    if (window.modalPanel) {
        // Добавляем пользовательский event для отслеживания открытия модального окна
        const originalOpenModal = window.modalPanel.openModal;
        
        // Переопределяем метод openModal для генерации события
        window.modalPanel.openModal = function(modalId) {
            const result = originalOpenModal.call(this, modalId);
            
            if (result) {
                // Генерируем пользовательское событие после открытия модального окна
                const modal = document.getElementById(modalId);
                if (modal) {
                    const event = new Event('show.modal-panel');
                    modal.dispatchEvent(event);
                }
            }
            
            return result;
        };
    }
});
</script><?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-panel.blade.php ENDPATH**/ ?>