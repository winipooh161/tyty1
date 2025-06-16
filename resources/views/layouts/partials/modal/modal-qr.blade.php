<!-- QR сканер модальное окно -->
<div class="modal-panel" id="qr-scanner-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-panel-dialog modal-fullscreen">
        <div class="modal-panel-content">
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

// Флаг для предотвращения одновременной инициализации нескольких экземпляров
let qrScannerInitializing = false;

// Модуль инициализации QR сканера
function initQrScannerModule(modalSystem) {
    // Предотвращаем одновременное выполнение нескольких инициализаций
    if (qrScannerInitializing) {
        console.warn('Инициализация QR-сканера уже выполняется, пропускаем повторный запрос');
        return;
    }
    
    // Проверяем, инициализирован ли уже сканер
    if (modalSystem.scannerInitialized && modalSystem.qrScanner) {
        console.log('QR сканер уже инициализирован, пропускаем повторную инициализацию');
        
        // Просто запускаем сканер, если он уже инициализирован, но остановлен
        if (modalSystem.qrScanner && typeof modalSystem.qrScanner.start === 'function') {
            try {
                const statusElement = document.getElementById('scannerStatus');
                if (statusElement) statusElement.textContent = 'Запуск камеры...';
                
                // Добавляем небольшую задержку перед запуском
                setTimeout(async () => {
                    try {
                        await modalSystem.qrScanner.start();
                        if (statusElement) statusElement.textContent = 'Наведите камеру на QR-код';
                        console.log('QR сканер перезапущен');
                    } catch (e) {
                        console.error('Ошибка при перезапуске QR сканера:', e);
                        handleScannerError(e, 
                            document.getElementById('scannerStatus'),
                            document.getElementById('scannerResult'), 
                            modalSystem
                        );
                    }
                }, 300);
            } catch (e) {
                console.error('Ошибка при попытке перезапуска QR сканера:', e);
            }
        }
        return;
    }
    
    // Устанавливаем флаг инициализации
    qrScannerInitializing = true;
    
    (async function() {
        try {
            const statusElement = document.getElementById('scannerStatus');
            const resultElement = document.getElementById('scannerResult');
            
            // Проверка на наличие элемента видео
            const video = document.getElementById('qrScannerVideo');
            if (!video) {
                qrScannerInitializing = false;
                throw new Error('Элемент видео не найден');
            }
            
            // Если видео уже имеет srcObject, удаляем его перед новой инициализацией
            if (video.srcObject) {
                try {
                    const tracks = video.srcObject.getTracks();
                    tracks.forEach(track => track.stop());
                    video.srcObject = null;
                    console.log('Существующий видеопоток очищен');
                } catch (e) {
                    console.warn('Не удалось очистить предыдущий видеопоток:', e);
                }
            }
            
            // Очищаем предыдущие сообщения
            if (statusElement) statusElement.textContent = 'Запрос доступа к камере...';
            if (resultElement) resultElement.textContent = '';
            
            // Логирование для отладки
            console.log('Инициализация QR сканера...');
            
            // Проверка наличия QrScanner и, если нужно, его загрузка
            if (typeof QrScanner === 'undefined' || QrScanner.constructor.name === 'MinimalQrScanner') {
                if (statusElement) statusElement.textContent = 'Загрузка библиотеки QR-сканера...';
                console.log('Загрузка библиотеки QR-сканера...');
                try {
                    await loadQrScannerLibrary();
                    if (statusElement) statusElement.textContent = 'Библиотека загружена, получаем доступ к камере...';
                    console.log('Библиотека QR-сканера загружена успешно');
                } catch(e) {
                    console.error('Ошибка загрузки библиотеки:', e);
                    if (statusElement) statusElement.textContent = 'Ошибка загрузки библиотеки сканера';
                    qrScannerInitializing = false;
                    throw new Error('Не удалось загрузить библиотеку QR-сканера');
                }
            }
            
            // Настройка пути к worker-скрипту (если еще не установлен)
            if (QrScanner.WORKER_PATH === undefined) {
                console.log('Настройка пути к worker-скрипту');
                QrScanner.WORKER_PATH = '/js/qr-scanner-worker.min.js';
            }
            
            // Проверка доступности камеры
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                qrScannerInitializing = false;
                throw new Error('Ваше устройство не поддерживает доступ к камере');
            }
            
            // Остановка предыдущего сканера, если он существует
            if (modalSystem.qrScanner) {
                try {
                    console.log('Останавливаем предыдущий экземпляр QR-сканера...');
                    modalSystem.qrScanner.stop();
                    modalSystem.qrScanner.destroy();
                    modalSystem.qrScanner = null;
                    modalSystem.scannerInitialized = false;
                    
                    // Добавим паузу для гарантированного освобождения ресурсов
                    await new Promise(resolve => setTimeout(resolve, 300));
                } catch (e) {
                    console.warn('Ошибка при остановке предыдущего QR-сканера:', e);
                    // Продолжаем выполнение, несмотря на ошибку
                }
            }
            
            // Запрашиваем разрешение на использование камеры заранее
            let mediaStream = null;
            try {
                console.log('Запрашиваем разрешение на использование камеры...');
                mediaStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' }
                });
                console.log('Разрешение на использование камеры получено');
                
                // Если успешно получили поток, освобождаем его для QrScanner
                mediaStream.getTracks().forEach(track => track.stop());
            } catch (permissionError) {
                console.error('Ошибка при запросе разрешения на камеру:', permissionError);
                // Продолжаем, QrScanner сам запросит разрешение
            }

            // Создаем экземпляр QR-сканера с обработкой ошибок
            console.log('Создание экземпляра QR-сканера...');
            
            // Параметры для QR-сканера (добавляем улучшенные параметры)
            const qrScannerOptions = {
                // Улучшаем чтение QR-кодов с экрана
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 10, // Увеличиваем частоту сканирования
                preferredCamera: 'environment', // Предпочтительно задняя камера
                calculateScanRegion: (video) => {
                    // Расширяем область сканирования до 80% экрана
                    const videoWidth = video.videoWidth;
                    const videoHeight = video.videoHeight;
                    const width = Math.min(videoWidth, videoHeight) * 0.8;
                    const height = width;
                    const x = (videoWidth - width) / 2;
                    const y = (videoHeight - height) / 2;
                    return { x, y, width, height };
                }
            };
            
            // Создаем сканер с улучшенной обработкой ошибок
            try {
                modalSystem.qrScanner = new QrScanner(
                    video,
                    result => {
                        // Обработка успешного сканирования
                        console.log('QR-код успешно отсканирован:', result.data);
                        if (statusElement) statusElement.textContent = 'QR-код найден!';
                        if (resultElement) resultElement.textContent = 'Обработка результата...';
                        
                        // Вибрация при успешном сканировании
                        if (navigator.vibrate && window.userHasInteractedWithPage) {
                            navigator.vibrate([100, 50, 100]);
                        }
                        
                        // Останавливаем сканирование
                        if (modalSystem.qrScanner) {
                            modalSystem.qrScanner.stop();
                        }
                        
                        // Проверяем, является ли результат ссылкой
                        if (result.data.startsWith('http')) {
                            console.log('Результат сканирования - ссылка:', result.data);
                            
                            // Показываем результат сканирования
                            if (resultElement) {
                                resultElement.innerHTML = `
                                    <div class="alert alert-success">
                                        <strong>Обнаружена ссылка:</strong><br>
                                        <small class="text-truncate d-block">${result.data}</small>
                                    </div>
                                    <div class="d-flex justify-content-between gap-2 mt-3">
                                        <button class="btn btn-sm btn-secondary" id="retryScanButton">
                                            <i class="bi bi-arrow-repeat me-1"></i>Сканировать снова
                                        </button>
                                        <a href="${result.data}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Перейти
                                        </a>
                                    </div>
                                `;
                            }
                            
                            // Обработчик для повторного сканирования
                            document.getElementById('retryScanButton')?.addEventListener('click', () => {
                                if (resultElement) resultElement.innerHTML = '';
                                if (statusElement) statusElement.textContent = 'Наведите камеру на QR-код';
                                if (modalSystem.qrScanner) modalSystem.qrScanner.start();
                            });
                        } else {
                            // Если не ссылка, просто показываем результат
                            if (resultElement) {
                                resultElement.innerHTML = `
                                    <div class="alert alert-info">
                                        <strong>Обнаружен текст:</strong><br>
                                        ${result.data}
                                    </div>
                                    <button class="btn btn-sm btn-primary mt-2" id="retryScanButton">
                                        <i class="bi bi-arrow-repeat me-1"></i>Сканировать снова
                                    </button>
                                `;
                            }
                            
                            // Обработчик для повторного сканирования
                            document.getElementById('retryScanButton')?.addEventListener('click', () => {
                                if (resultElement) resultElement.innerHTML = '';
                                if (statusElement) statusElement.textContent = 'Наведите камеру на QR-код';
                                if (modalSystem.qrScanner) modalSystem.qrScanner.start();
                            });
                        }
                    },
                    error => {
                        // Обработка ошибок сканирования (не останавливаем сканирование при ошибке)
                        console.error('QR сканер: ошибка сканирования', error);
                    },
                    qrScannerOptions
                );
            } catch (initError) {
                qrScannerInitializing = false;
                console.error('Ошибка инициализации QR-сканера:', initError);
                throw initError;
            }
            
            try {
                // Безопасно проверяем наличие камер, обрабатывая возможные ошибки
                let cameras = [];
                try {
                    console.log('Получение списка камер...');
                    cameras = await QrScanner.listCameras(true);
                    console.log('Доступные камеры:', cameras);
                    
                    // Настраиваем переключение камер, если их больше одной
                    const switchCameraBtn = document.getElementById('switchCameraBtn');
                    if (switchCameraBtn && cameras.length > 1) {
                        let currentCameraIndex = 0;
                        
                        switchCameraBtn.addEventListener('click', async () => {
                            if (!modalSystem.qrScanner) return;
                            
                            try {
                                // Останавливаем текущую камеру
                                modalSystem.qrScanner.stop();
                                
                                // Меняем индекс камеры
                                currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                                
                                // Небольшая задержка для корректного переключения
                                await new Promise(resolve => setTimeout(resolve, 300));
                                
                                // Устанавливаем новую камеру
                                await modalSystem.qrScanner.setCamera(cameras[currentCameraIndex].id);
                                
                                if (statusElement) {
                                    statusElement.textContent = `Переключено на камеру: ${cameras[currentCameraIndex].label || 'Камера ' + (currentCameraIndex + 1)}`;
                                }
                            } catch (e) {
                                console.error('Ошибка при переключении камеры:', e);
                                
                                // В случае ошибки пробуем перезапустить сканер
                                try {
                                    await modalSystem.qrScanner.start();
                                } catch (restartError) {
                                    console.error('Ошибка при перезапуске сканера:', restartError);
                                }
                            }
                        });
                        
                        switchCameraBtn.style.display = 'block';
                    } else if (switchCameraBtn) {
                        switchCameraBtn.style.display = 'none';
                    }
                    
                    // Настраиваем кнопку вспышки, если она доступна
                    const toggleFlashlightBtn = document.getElementById('toggleFlashlightBtn');
                    if (toggleFlashlightBtn) {
                        // Проверяем доступность функции для управления вспышкой
                        let flashSupported = false;
                        
                        // Первоначально скрываем кнопку
                        toggleFlashlightBtn.style.display = 'none';
                        
                        try {
                            // Проверяем, поддерживается ли функция toggleFlash и hasFlash
                            if (modalSystem.qrScanner && 
                                typeof modalSystem.qrScanner.hasFlash === 'function' &&
                                typeof modalSystem.qrScanner.toggleFlash === 'function') {
                                
                                // Проверяем доступность вспышки на устройстве
                                modalSystem.qrScanner.hasFlash()
                                    .then(hasFlash => {
                                        flashSupported = hasFlash;
                                        
                                        // Показываем кнопку только если вспышка поддерживается
                                        toggleFlashlightBtn.style.display = flashSupported ? 'block' : 'none';
                                        
                                        if (flashSupported) {
                                            console.log('Вспышка поддерживается на этом устройстве');
                                        } else {
                                            console.log('Вспышка не поддерживается на этом устройстве');
                                        }
                                    })
                                    .catch(err => {
                                        console.warn('Ошибка при проверке поддержки вспышки:', err);
                                        toggleFlashlightBtn.style.display = 'none';
                                    });
                            } else {
                                console.log('Метод toggleFlash или hasFlash не найден в QR сканере');
                                toggleFlashlightBtn.style.display = 'none';
                            }
                        } catch (e) {
                            console.warn('Ошибка при настройке кнопки вспышки:', e);
                            toggleFlashlightBtn.style.display = 'none';
                        }
                        
                        // Обработчик нажатия на кнопку управления вспышкой
                        toggleFlashlightBtn.addEventListener('click', async () => {
                            if (!modalSystem.qrScanner) return;
                            
                            try {
                                if (typeof modalSystem.qrScanner.toggleFlash === 'function') {
                                    await modalSystem.qrScanner.toggleFlash();
                                    toggleFlashlightBtn.classList.toggle('active');
                                    console.log('Вспышка переключена');
                                } else {
                                    console.warn('Функция toggleFlash не найдена в QR сканере');
                                    if (statusElement) statusElement.textContent = 'Вспышка недоступна';
                                    setTimeout(() => {
                                        if (statusElement) statusElement.textContent = 'Наведите камеру на QR-код';
                                    }, 2000);
                                }
                            } catch (flashError) {
                                console.error('Ошибка управления вспышкой:', flashError);
                                if (statusElement) {
                                    statusElement.textContent = 'Вспышка недоступна';
                                    setTimeout(() => {
                                        statusElement.textContent = 'Наведите камеру на QR-код';
                                    }, 2000);
                                }
                            }
                        });
                    }
                    
                } catch (e) {
                    console.warn('Ошибка при получении списка камер:', e);
                }
                
                if (statusElement) statusElement.textContent = 'Запуск камеры...';
                console.log('Запуск камеры...');
                
                // Увеличиваем задержку перед запуском, чтобы компоненты успели инициализироваться
                setTimeout(async () => {
                    try {
                        if (modalSystem.qrScanner) {
                            await modalSystem.qrScanner.start();
                            if (statusElement) statusElement.textContent = 'Наведите камеру на QR-код';
                            console.log('QR сканер успешно запущен');
                            
                            // Проверяем поддержку вспышки после запуска камеры
                            try {
                                if (typeof modalSystem.qrScanner.hasFlash === 'function') {
                                    const hasFlash = await modalSystem.qrScanner.hasFlash();
                                    const toggleFlashlightBtn = document.getElementById('toggleFlashlightBtn');
                                    if (toggleFlashlightBtn) {
                                        toggleFlashlightBtn.style.display = hasFlash ? 'block' : 'none';
                                        console.log('Статус поддержки вспышки после запуска:', hasFlash);
                                    }
                                }
                            } catch (flashCheckError) {
                                console.warn('Ошибка при проверке вспышки после запуска камеры:', flashCheckError);
                            }
                            
                            modalSystem.scannerInitialized = true;
                        } else {
                            console.error('QR сканер не был инициализирован');
                            if (statusElement) statusElement.textContent = 'Ошибка инициализации сканера';
                        }
                    } catch (startError) {
                        console.error('Ошибка при запуске QR сканера:', startError);
                        handleScannerError(startError, statusElement, resultElement, modalSystem);
                    } finally {
                        // Снимаем флаг инициализации
                        qrScannerInitializing = false;
                    }
                }, 800);
            } catch (startError) {
                // Более подробный вывод ошибки
                console.error('Ошибка при запуске QR сканера:', startError);
                handleScannerError(startError, statusElement, resultElement, modalSystem);
                
                // Снимаем флаг инициализации
                qrScannerInitializing = false;
            }
            
        } catch (error) {
            console.error('Ошибка при инициализации QR сканера:', error);
            
            const statusElement = document.getElementById('scannerStatus');
            const resultElement = document.getElementById('scannerResult');
            
            handleScannerError(error, statusElement, resultElement, modalSystem);
            
            // Снимаем флаг инициализации
            qrScannerInitializing = false;
        }
    })();
}

// Функция для обработки ошибок сканера с улучшенной обработкой AbortError
function handleScannerError(error, statusElement, resultElement, modalSystem) {
    let errorMessage = 'Проверьте, что у вас есть камера и вы дали разрешение на её использование';
    let isAbortError = false;
    
    // Проверяем наличие AbortError в стеке ошибок
    if (error.name === 'AbortError' || error.message?.includes('AbortError') || error.toString().includes('AbortError')) {
        errorMessage = 'Запуск камеры был прерван. Повторите попытку через несколько секунд.';
        isAbortError = true;
        console.warn('Обнаружена AbortError, планируем автоматический перезапуск');
    }
    // Определяем другие типы ошибок для более информативного сообщения
    else if (error.name === 'NotAllowedError') {
        errorMessage = 'Доступ к камере запрещен. Пожалуйста, предоставьте разрешение в настройках браузера.';
    } else if (error.name === 'NotFoundError') {
        errorMessage = 'Камера не найдена. Проверьте подключение камеры.';
    } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
        errorMessage = 'Камера уже используется другим приложением или недоступна.';
    } else if (error.name === 'SecurityError') {
        errorMessage = 'Использование камеры заблокировано политикой безопасности.';
    } else if (error.name === 'OverconstrainedError') {
        errorMessage = 'Не найдена камера, соответствующая заданным требованиям.';
    }

    if (statusElement) statusElement.textContent = 'Ошибка доступа к камере';
    if (resultElement) {
        resultElement.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${error.message || errorMessage}
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button class="btn btn-primary" id="retryCamera">
                    <i class="bi bi-arrow-clockwise me-1"></i> Повторить
                </button>
                <a href="https://support.google.com/chrome/answer/2693767" target="_blank" class="btn btn-link ms-2">
                    Помощь <i class="bi bi-question-circle"></i>
                </a>
            </div>
        `;
    }
    
    // Добавляем обработчик кнопки "Повторить"
    document.getElementById('retryCamera')?.addEventListener('click', () => {
        if (resultElement) resultElement.innerHTML = '';
        stopQrScannerModule(modalSystem);
        modalSystem.scannerInitialized = false;
        setTimeout(() => initQrScannerModule(modalSystem), 500);
    });
    
    // Если это AbortError, попробуем автоматически перезапустить через 2 секунды
    if (isAbortError) {
        setTimeout(() => {
            console.log('Автоматический перезапуск QR-сканера после AbortError...');
            if (resultElement) resultElement.innerHTML = '';
            stopQrScannerModule(modalSystem);
            modalSystem.scannerInitialized = false;
            setTimeout(() => initQrScannerModule(modalSystem), 500);
        }, 2000);
    }
}

// Функция для остановки QR сканера
function stopQrScannerModule(modalSystem) {
    console.log('Остановка QR сканера...');
    
    // Сбрасываем флаг инициализации
    qrScannerInitializing = false;
    
    if (modalSystem.qrScanner) {
        try {
            modalSystem.qrScanner.stop();
            modalSystem.qrScanner.destroy();
            console.log('QR сканер остановлен');
        } catch (e) {
            console.warn('Ошибка при остановке QR сканера:', e);
        }
        modalSystem.qrScanner = null;
    }
    modalSystem.scannerInitialized = false;
    
    // Очищаем видеоэлемент
    try {
        const videoElem = document.getElementById('qrScannerVideo');
        if (videoElem && videoElem.srcObject) {
            const tracks = videoElem.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            videoElem.srcObject = null;
            console.log('Видеопоток очищен');
        }
    } catch (e) {
        console.warn('Ошибка при очистке видеопотока:', e);
    }
}

// Функция для загрузки библиотеки QR-сканера
function loadQrScannerLibrary() {
    return new Promise((resolve, reject) => {
        // Если библиотека уже загружена, сразу резолвим
        if (typeof QrScanner !== 'undefined' && QrScanner.constructor.name !== 'MinimalQrScanner') {
            console.log('Библиотека QrScanner уже загружена');
            resolve();
            return;
        }
        
        let timeout = setTimeout(() => {
            reject(new Error('Время ожидания загрузки библиотеки истекло'));
        }, 10000);
        
        // Проверяем наличие библиотеки в window перед загрузкой
        if (window.QrScannerLoading) {
            console.log('Загрузка библиотеки уже в процессе, ожидаем...');
            window.addEventListener('qrScannerLoaded', () => {
                clearTimeout(timeout);
                resolve();
            }, { once: true });
            return;
        }
        
        console.log('Загрузка библиотеки QR сканера...');
        window.QrScannerLoading = true;
        
        // Создаем обработчик ошибок для отслеживания проблем с загрузкой
        const handleLoadError = (error) => {
            clearTimeout(timeout);
            window.QrScannerLoading = false;
            console.error('Ошибка загрузки QR-сканера:', error);
            reject(new Error(`Не удалось загрузить библиотеку QR сканера: ${error.message || 'неизвестная ошибка'}`));
        };
        
        const script = document.createElement('script');
        script.src = '/js/qr-scanner.min.js';
        script.async = true;
        script.crossOrigin = "anonymous"; // Добавляем для отладки CORS проблем
        script.onload = () => {
            clearTimeout(timeout);
            
            // После загрузки основного скрипта, устанавливаем путь к worker
            if (QrScanner && QrScanner.WORKER_PATH === undefined) {
                QrScanner.WORKER_PATH = '/js/qr-scanner-worker.min.js';
            }
            
            console.log('Библиотека QR-сканера загружена');
            window.QrScannerLoading = false;
            window.dispatchEvent(new Event('qrScannerLoaded'));
            
            // Проверяем наличие метода toggleFlash для отладки
            if (QrScanner && QrScanner.prototype) {
                console.log('Проверка методов QrScanner:',
                    'hasFlash:', typeof QrScanner.prototype.hasFlash === 'function', 
                    'toggleFlash:', typeof QrScanner.prototype.toggleFlash === 'function');
            }
            
            resolve();
        };
        script.onerror = handleLoadError;
        
        // Добавляем обработку ошибок для скрипта
        script.addEventListener('error', handleLoadError);
        
        document.head.appendChild(script);
        
        // Также загружаем worker
        const workerPreload = document.createElement('link');
        workerPreload.rel = 'preload';
        workerPreload.href = '/js/qr-scanner-worker.min.js';
        workerPreload.as = 'script';
        document.head.appendChild(workerPreload);
    });
}

// Регистрируем функции для модальной системы
window.initQrScannerModule = initQrScannerModule;
window.stopQrScannerModule = stopQrScannerModule;

// Проверка на мобильные устройства для оптимизации
window.isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
</script>

<!-- Стили для QR сканера -->
<style>
.camera-container {
    position: relative;
    width: 100%;
    height: calc(100% - 105px);
    background-color: #000;
    overflow: hidden;
}

#qrScannerVideo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.3);
}

.scanner-frame {
    width: 70%;
    height: 40%;
    border: 2px solid #fff;
    border-radius: 10px;
    box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.3);
    position: relative;
}

.scanner-frame::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #fff, transparent);
    animation: scanLine 2s linear infinite;
}

@keyframes scanLine {
    0% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(calc(100% - 2px));
    }
    100% {
        transform: translateY(0);
    }
}

.scanning-status {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.9);
    padding: 15px;
    text-align: center;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.scanner-controls {
    border-top: 1px solid #dee2e6;
}

/* Стиль для кнопок камеры */
#switchCameraBtn.active, #toggleFlashlightBtn.active {
    background-color: #0d6efd;
    color: white;
}
</style>
