<!-- Модальное окно для функции "Поделиться профилем" -->
<div class="modal-panel" id="share-profile-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-panel-dialog">
        <div class="modal-panel-content">
            <div class="modal-panel-header">
                <h5 class="modal-panel-title">Поделиться профилем</h5>
                <button type="button" class="modal-panel-close" onclick="window.modalPanel.closeModal()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-panel-body">
                <!-- Секция для QR-кода -->
                <div class="qr-section text-center mb-4">
                    <div class="qr-container mx-auto mb-3" id="profile-qr-container">
                        <div class="placeholder-loading d-flex align-items-center justify-content-center" style="height: 200px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted mb-1">Отсканируйте QR-код, чтобы открыть профиль</p>
                    <div class="qr-actions">
                        <button class="btn btn-sm btn-outline-primary" id="download-qr-btn">
                            <i class="bi bi-download me-1"></i> Скачать QR-код
                        </button>
                    </div>
                </div>
                
                <!-- Секция для прямой ссылки -->
                <div class="link-section mb-4">
                    <h6>Прямая ссылка</h6>
                    <div class="input-group">
                        <input type="text" class="form-control" id="profile-link-input" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copy-link-btn">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <div class="form-text">Скопируйте ссылку и поделитесь ей с друзьями</div>
                </div>
                
                <!-- Секция для социальных сетей -->
                <div class="social-section">
                    <h6>Поделиться в социальных сетях</h6>
                    <div class="social-buttons d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary share-btn" data-social="telegram" title="Поделиться в Telegram">
                            <i class="bi bi-telegram"></i>
                        </button>
                        <button class="btn btn-outline-primary share-btn" data-social="whatsapp" title="Поделиться в WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </button>
                        <button class="btn btn-outline-primary share-btn" data-social="vk" title="Поделиться ВКонтакте">
                            <i class="bi bi-chat-fill"></i>
                        </button>
                        <button class="btn btn-outline-primary share-btn" data-social="email" title="Отправить по Email">
                            <i class="bi bi-envelope"></i>
                        </button>
                        <button class="btn btn-outline-primary share-btn" data-social="messenger" title="Поделиться в Messenger">
                            <i class="bi bi-chat-dots"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Ссылка на дополнительные действия -->
                <div class="mt-4 text-center">
                    <a href="#" class="text-decoration-none" id="embed-profile-link">
                        <i class="bi bi-code-slash me-1"></i> Встроить профиль на свой сайт
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Переменные для хранения текущей информации о профиле
    let currentProfileId = null;
    let currentProfileUrl = null;
    
    // Загружаем библиотеку QR Code
    function loadQRCodeLibrary() {
        return new Promise((resolve, reject) => {
            if (window.QRCode) {
                resolve(window.QRCode);
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js';
            script.onload = () => resolve(window.QRCode);
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    // Создаем QR код
    function generateQRCode(url) {
        loadQRCodeLibrary()
            .then(QRCode => {
                const container = document.getElementById('profile-qr-container');
                container.innerHTML = '';
                
                QRCode.toCanvas(container, url, {
                    width: 200,
                    margin: 2,
                    color: {
                        dark: '#000',
                        light: '#fff'
                    }
                }, function(error) {
                    if (error) {
                        console.error(error);
                        container.innerHTML = `<div class="alert alert-danger">Ошибка создания QR-кода</div>`;
                    }
                    
                    // Добавляем канвас в контейнер
                    const canvas = container.querySelector('canvas');
                    if (canvas) {
                        canvas.style.display = 'block';
                        canvas.style.margin = 'auto';
                        canvas.classList.add('img-fluid', 'border', 'rounded');
                    }
                });
            })
            .catch(error => {
                console.error("Ошибка загрузки библиотеки QR-кода:", error);
                document.getElementById('profile-qr-container').innerHTML = 
                    `<div class="alert alert-danger">Не удалось загрузить генератор QR-кода</div>`;
            });
    }
    
    // Обработчик открытия модального окна
    document.addEventListener('modal.opened', function(event) {
        if (event.detail?.modalId === 'share-profile-modal') {
            const userId = event.detail?.userId || null;
            setupShareModal(userId);
        }
    });
    
    // Настраиваем модальное окно поделиться профилем
    function setupShareModal(userId) {
        // Если ID не передан, используем текущего пользователя
        const profileId = userId || "<?php echo e(Auth::id()); ?>";
        currentProfileId = profileId;
        
        // Формируем URL профиля
        currentProfileUrl = `${window.location.origin}/users/${profileId}/templates`;
        
        // Устанавливаем ссылку в поле ввода
        document.getElementById('profile-link-input').value = currentProfileUrl;
        
        // Генерируем QR-код
        generateQRCode(currentProfileUrl);
        
        // Устанавливаем обработчики кнопок социальных сетей
        setupSocialButtons();
    }
    
    // Настраиваем кнопки социальных сетей
    function setupSocialButtons() {
        const shareButtons = document.querySelectorAll('.share-btn');
        
        shareButtons.forEach(button => {
            button.addEventListener('click', function() {
                const socialNetwork = this.getAttribute('data-social');
                shareProfile(socialNetwork);
            });
        });
    }
    
    // Обработчик кнопки копирования ссылки
    document.getElementById('copy-link-btn').addEventListener('click', function() {
        const linkInput = document.getElementById('profile-link-input');
        linkInput.select();
        linkInput.setSelectionRange(0, 99999); // Для мобильных устройств
        
        try {
            // Современный способ копирования в буфер обмена
            navigator.clipboard.writeText(linkInput.value)
                .then(() => showToast('Ссылка скопирована в буфер обмена'))
                .catch(() => {
                    // Запасной вариант
                    document.execCommand('copy');
                    showToast('Ссылка скопирована в буфер обмена');
                });
        } catch(err) {
            showToast('Не удалось скопировать ссылку', 'error');
        }
    });
    
    // Обработчик кнопки скачивания QR-кода
    document.getElementById('download-qr-btn').addEventListener('click', function() {
        const canvas = document.querySelector('#profile-qr-container canvas');
        
        if (!canvas) {
            showToast('QR-код не сгенерирован', 'error');
            return;
        }
        
        try {
            const image = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            
            link.href = image;
            link.download = `profile-qr-${currentProfileId}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showToast('QR-код загружается');
        } catch(err) {
            console.error(err);
            showToast('Ошибка при скачивании QR-кода', 'error');
        }
    });
    
    // Обработчик клика по ссылке встраивания
    document.getElementById('embed-profile-link').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Показываем пользователю код для встраивания
        const embedCode = `<iframe src="${currentProfileUrl}" width="100%" height="600" frameborder="0"></iframe>`;
        
        // Помещаем код для встраивания в буфер обмена
        try {
            navigator.clipboard.writeText(embedCode)
                .then(() => {
                    showToast('Код для встраивания скопирован в буфер обмена');
                    
                    // Показываем подсказку с примером кода
                    alert('Код для встраивания скопирован в буфер обмена:\n\n' + embedCode);
                })
                .catch(err => {
                    console.error('Не удалось скопировать код: ', err);
                    showToast('Не удалось скопировать код', 'error');
                    
                    // Показываем подсказку с примером кода
                    alert('Используйте следующий HTML-код для встраивания профиля на сайт:\n\n' + embedCode);
                });
        } catch(err) {
            console.error(err);
            showToast('Не удалось скопировать код для встраивания', 'error');
            
            // Запасной вариант
            alert('Используйте следующий HTML-код для встраивания профиля на сайт:\n\n' + embedCode);
        }
    });
    
    // Функция для поделиться профилем в социальных сетях
    function shareProfile(social) {
        const url = encodeURIComponent(currentProfileUrl);
        const title = encodeURIComponent('Посмотрите мой профиль!');
        let shareUrl = '';
        
        switch (social) {
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${url}&text=${title}`;
                break;
            case 'whatsapp':
                shareUrl = `https://api.whatsapp.com/send?text=${title}%20${url}`;
                break;
            case 'vk':
                shareUrl = `https://vk.com/share.php?url=${url}&title=${title}`;
                break;
            case 'email':
                shareUrl = `mailto:?subject=${title}&body=${url}`;
                break;
            case 'messenger':
                shareUrl = `https://www.facebook.com/dialog/send?link=${url}&app_id=123456789&redirect_uri=${url}`;
                break;
            default:
                showToast('Неизвестная социальная сеть', 'error');
                return;
        }
        
        // Открываем окно для шеринга
        window.open(shareUrl, '_blank');
    }
    
    // Функция для отображения уведомлений
    function showToast(message, type = 'success') {
        // Проверяем наличие функции в глобальной области видимости
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            // Реализация функции, если она отсутствует в глобальной области
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }
});
</script>

<style>
/* Стили для модального окна "Поделиться профилем" */
.qr-container {
    max-width: 240px;
    height: auto;
    padding: 1rem;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.qr-container canvas {
    max-width: 100%;
    height: auto;
}

.social-buttons .btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.social-buttons .btn i {
    font-size: 1.25rem;
}

.social-buttons .btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.social-buttons .btn[data-social="telegram"] {
    color: #0088cc;
    border-color: #0088cc;
}

.social-buttons .btn[data-social="telegram"]:hover {
    background-color: #0088cc;
    color: #fff;
}

.social-buttons .btn[data-social="whatsapp"] {
    color: #25d366;
    border-color: #25d366;
}

.social-buttons .btn[data-social="whatsapp"]:hover {
    background-color: #25d366;
    color: #fff;
}

.social-buttons .btn[data-social="vk"] {
    color: #4C75A3;
    border-color: #4C75A3;
}

.social-buttons .btn[data-social="vk"]:hover {
    background-color: #4C75A3;
    color: #fff;
}

.social-buttons .btn[data-social="email"] {
    color: #D44638;
    border-color: #D44638;
}

.social-buttons .btn[data-social="email"]:hover {
    background-color: #D44638;
    color: #fff;
}

.social-buttons .btn[data-social="messenger"] {
    color: #0078FF;
    border-color: #0078FF;
}

.social-buttons .btn[data-social="messenger"]:hover {
    background-color: #0078FF;
    color: #fff;
}

/* Анимация для плейсхолдера QR-кода */
.placeholder-loading {
    background: linear-gradient(90deg, #f0f0f0, #f8f8f8, #f0f0f0);
    background-size: 600% 600%;
    animation: loading-animation 1.5s ease infinite;
    border-radius: 8px;
}

@keyframes loading-animation {
    0% {background-position: 0% 50%}
    50% {background-position: 100% 50%}
    100% {background-position: 0% 50%}
}
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-share-profile.blade.php ENDPATH**/ ?>