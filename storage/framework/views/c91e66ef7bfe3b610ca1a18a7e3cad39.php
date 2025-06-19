<!-- filepath: c:\ospanel\domains\tyty\resources\views\layouts\partials\modal\modal-qr.blade.php -->
<!-- QR сканер модальное окно -->
<div class="modal-panel fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true" data-static="true">
    <div class="modal-backdrop" id="qrScannerBackdrop"></div>
    <div class="modal-panel-dialog modal-fullscreen">
        <div class="modal-panel-content">
            <div class="modal-panel-body p-0 position-relative">
                <div class="camera-container">
                    <video id="qrScannerVideo" playsinline muted></video>
                    <div class="scanner-overlay d-flex flex-column align-items-center justify-content-center">
                        <div class="scanner-frame">
                            <!-- Анимированные уголки для рамки сканирования -->
                            <div class="scanner-corner top-left"></div>
                            <div class="scanner-corner top-right"></div>
                            <div class="scanner-corner bottom-left"></div>
                            <div class="scanner-corner bottom-right"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Скрытые индикаторы для работы JavaScript -->
                <div id="qrScannerLoading" class="scanner-loading align-items-center justify-content-center" style="display: none;"></div>
                <div id="qrScannerError" class="scanner-error align-items-center justify-content-center" style="display: none;">
                    <p id="qrScannerErrorMessage" class="d-none"></p>
                    <button id="qrScannerRetryBtn" class="d-none"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Стили для модального окна с QR сканером */
.camera-container {
    position: relative;
    width: 100%;
    height: 100vh;
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
    background: rgba(0, 0, 0, 0.3);
}

.scanner-frame {
    width: 70%;
    height: 40%;
    max-width: 300px;
    max-height: 300px;
    border-radius: 10px;
    box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.3);
    position: relative;
    border: none;
}

/* Стилизованные уголки для рамки сканирования */
.scanner-corner {
    position: absolute;
    width: 20px;
    height: 20px;
    border-color: #fff;
    border-style: solid;
    border-width: 3px;
}

.top-left {
    top: 0;
    left: 0;
    border-right: none;
    border-bottom: none;
    border-top-left-radius: 5px;
}

.top-right {
    top: 0;
    right: 0;
    border-left: none;
    border-bottom: none;
    border-top-right-radius: 5px;
}

.bottom-left {
    bottom: 0;
    left: 0;
    border-right: none;
    border-top: none;
    border-bottom-left-radius: 5px;
}

.bottom-right {
    bottom: 0;
    right: 0;
    border-left: none;
    border-top: none;
    border-bottom-right-radius: 5px;
}

/* Анимированная линия сканирования */
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

/* Скрытые стили для состояний загрузки и ошибки */
.scanner-loading, .scanner-error {
    display: none !important;
}
</style>

<script>
/**
 * Класс для управления системой сканирования QR-кодов
 */
class QRScannerController {
    constructor() {
        // DOM элементы
        this.modal = document.getElementById('qrScannerModal');
        this.backdrop = document.getElementById('qrScannerBackdrop');
        this.videoElem = document.getElementById('qrScannerVideo');
        this.loadingElem = document.getElementById('qrScannerLoading');
        this.errorElem = document.getElementById('qrScannerError');
        this.errorMessageElem = document.getElementById('qrScannerErrorMessage');
        this.retryBtn = document.getElementById('qrScannerRetryBtn');
        
        // Настройки и состояние
        this.qrScanner = null;
        this.isScanning = false;
        this.isInitialized = false;
        this.isClosing = false;
        this.isSafeToOpen = true;
    }
    
    init() {
        // Проверяем наличие необходимых элементов DOM
        if (!this.modal || !this.videoElem) {
            console.error('QRScanner: Необходимые элементы DOM не найдены');
            return;
        }
        
        this.setupEventHandlers();
        this.isInitialized = true;
        console.log('QRScanner: Контроллер инициализирован');
    }
    
    setupEventHandlers() {
        // Интеграция с модальной системой
        if (this.modal) {
            this.modal.addEventListener('show.modal-panel', () => {
                console.log('QRScanner: Модальное окно показано, запускаем сканер');
                setTimeout(() => this.startScanner(), 200);
            });
        }
        
        // Обработчик для кнопки повтора
        if (this.retryBtn) {
            this.retryBtn.addEventListener('click', () => this.startScanner());
        }
    }
    
    /**
     * Открыть модальное окно сканера
     */
    open(event) {
        // Проверяем, является ли аргумент событием DOM и имеет метод preventDefault
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        
        // Проверяем блокировки перед открытием
        if (window.modalClosingInProgress || window.qrScannerBlockOpen || 
            this.isClosing || !this.isSafeToOpen) {
            console.log('QRScanner: Предотвращено открытие (флаги защиты активны)');
            return false;
        }
        
        this.isSafeToOpen = false;
        
        // Делегируем открытие модальной системе
        if (window.modalPanel) {
            const result = window.modalPanel.openModal('qrScannerModal');
            
            setTimeout(() => {
                this.isSafeToOpen = true;
            }, 1000);
            
            return result;
        } else {
            console.warn('QRScanner: Модальная система не найдена');
            
            setTimeout(() => {
                this.isSafeToOpen = true;
            }, 1000);
            
            return false;
        }
    }
    
    /**
     * Закрыть модальное окно сканера
     */
    close() {
        console.log('QRScanner: Закрытие через контроллер');
        
        this.isClosing = true;
        window.qrScannerBlockOpen = true;
        
        // Останавливаем сканер
        this.stopScanner();
        
        // Делегируем закрытие модальной системе
        if (window.modalPanel) {
            window.modalPanel.closeModal(true);
        }
        
        setTimeout(() => {
            this.isClosing = false;
            window.qrScannerBlockOpen = false;
        }, 2000);
    }
    
    /**
     * Запускает сканер QR-кодов
     */
    async startScanner() {
        // Проверяем доступность библиотеки
        if (typeof QrScanner !== 'function') {
            console.error('QRScanner: Библиотека QrScanner не доступна');
            this.showError('Библиотека QR Scanner не загружена');
            return;
        }
        
        try {
            // Если сканер уже запущен, не создаем новый
            if (this.qrScanner && this.isScanning) {
                console.log('QRScanner: Сканер уже запущен');
                this.loadingElem.style.display = 'none';
                return;
            }
            
            // Останавливаем предыдущий сканер, если есть
            if (this.qrScanner) {
                this.stopScanner();
            }
            
            // Создаем сканер
            this.qrScanner = new QrScanner(
                this.videoElem,
                this.handleScan.bind(this),
                {
                    highlightScanRegion: false,
                    highlightCodeOutline: false,
                    returnDetailedScanResult: true
                }
            );
            
            // Запускаем сканер
            await this.qrScanner.start();
            this.isScanning = true;
            
            // Скрываем индикатор загрузки
            if (this.loadingElem) {
                this.loadingElem.style.display = 'none';
            }
            
            console.log('QRScanner: Сканер успешно запущен');
            
        } catch (error) {
            console.error('QRScanner: Ошибка при запуске сканера', error);
            this.showError('Не удалось запустить сканер: ' + error.message);
        }
    }
    
    /**
     * Останавливает сканер
     */
    stopScanner() {
        if (this.qrScanner && this.isScanning) {
            try {
                this.qrScanner.stop();
                this.qrScanner.destroy();
                this.qrScanner = null;
                this.isScanning = false;
                console.log('QRScanner: Сканер остановлен и уничтожен');
            } catch (error) {
                console.warn('QRScanner: Ошибка при остановке сканера', error);
                this.qrScanner = null;
                this.isScanning = false;
            }
        }
    }
    
    /**
     * Обрабатывает результат сканирования QR-кода
     */
    handleScan(scanResult) {
        if (!scanResult || !scanResult.data) {
            return;
        }
        
        const qrData = scanResult.data;
        console.log('QRScanner: Код отсканирован', qrData);
        
        // Воспроизводим звуковой эффект при успешном сканировании
        this.playSuccessSound();
        
        // Останавливаем сканер
        this.stopScanner();
        
        // Добавляем небольшую задержку перед закрытием модального окна и переходом
        setTimeout(() => {
            // Закрываем модальное окно
            this.close();
            
            // Проверяем, является ли результат действительным URL
            if (this.isValidUrl(qrData)) {
                window.location.href = qrData;
            } else {
                // Если не URL, вызываем событие
                document.dispatchEvent(new CustomEvent('qrCodeScanned', { 
                    detail: { data: qrData } 
                }));
            }
        }, 500);
    }
    
    /**
     * Проверяет, является ли строка допустимым URL
     */
    isValidUrl(str) {
        try {
            new URL(str);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    /**
     * Показывает ошибку в интерфейсе
     */
    showError(message) {
        if (this.loadingElem) this.loadingElem.style.display = 'none';
        if (this.errorElem) this.errorElem.style.display = 'flex';
        if (this.errorMessageElem) this.errorMessageElem.textContent = message;
        console.error('QRScanner Error:', message);
    }
    
    /**
     * Воспроизводит звук успешного сканирования
     */
    playSuccessSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(1800, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(500, audioContext.currentTime + 0.1);
            
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.1);
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (error) {
            console.warn('QRScanner: Невозможно воспроизвести звук', error);
        }
    }
}

// Инициализируем контроллер QR-сканера
document.addEventListener('DOMContentLoaded', () => {
    if (!window.qrScannerController) {
        window.qrScannerController = new QRScannerController();
        window.qrScannerController.init();
    }
    
    // Глобальные флаги для отслеживания состояния
    window.modalClosingInProgress = window.modalClosingInProgress || false;
    window.qrScannerBlockOpen = window.qrScannerBlockOpen || false;
    window.lastModalClosed = window.lastModalClosed || 0;
});
</script><?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-qr.blade.php ENDPATH**/ ?>