<!-- Модальное окно для шаринга профиля -->
<div class="modal-panel fade" id="share-profile-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-panel-dialog">
        <div class="modal-panel-content">
            
           
            <div class="modal-panel-body modal-panel-body-sub">
               
                
                <!-- QR код для профиля -->
                <div class="share-qr-container text-center  ">
                    <div id="profile-qr-code" class="qr-code-container mx-auto">
                        <!-- QR код будет сгенерирован здесь -->
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                    </div>
                  
                    <button class="btn btn-sm btn-outline-secondary mt-2" id="download-qr-code">
                        <i class="bi bi-download"></i>  QR-код
                    </button>
                </div>
                
                <!-- Копирование ссылки -->
                <div class="input-group mb-4">
                    <input type="text" id="share-profile-url" class="form-control" readonly value="<?php echo e(url('/users/'.Auth::id().'/templates')); ?>">
                    <button class="btn btn-primary copy-link-btn" type="button" id="copy-profile-url">
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                
                <!-- Кнопки социальных сетей -->
                <div class="share-social-buttons text-center">
                  
                    <div class="d-flex justify-content-center flex-wrap gap-3 mb-3" id="social-share-buttons">
                        <a href="#" class="btn btn-outline-primary share-vk" onclick="shareProfile('vk')">
                            <i class="bi bi-vk"></i> ВКонтакте
                        </a>
                        <a href="#" class="btn btn-outline-info share-telegram" onclick="shareProfile('telegram')">
                            <i class="bi bi-telegram"></i> 
                        </a>
                        <a href="#" class="btn btn-outline-primary share-whatsapp" onclick="shareProfile('whatsapp')">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                        <a href="#" class="btn btn-outline-danger share-mail" onclick="shareProfile('email')">
                            <i class="bi bi-envelope"></i>
                        </a>
                    </div>
                    
                    <!-- Нативный шаринг для мобильных устройств -->
                    <div id="native-share-container" class="my-3" style="display: none;">
                        <button class="btn btn-primary w-100" id="native-share-btn">
                            <i class="bi bi-share"></i> Поделиться
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Стили для модального окна шаринга -->
<style>
    .qr-code-container {
        width: 200px;
        height: 200px;
        padding: 10px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .qr-code-container img {
        width: 100%;
        height: auto;
    }
    
    .share-social-buttons .btn {
        min-width: 120px;
    }
    
    .copy-success {
        animation: fadeInOut 2s ease;
    }
    
    @keyframes fadeInOut {
        0% { background-color: transparent; }
        20% { background-color: #d4edda; }
        80% { background-color: #d4edda; }
        100% { background-color: transparent; }
    }
    
    /* Адаптивные стили для мобильных */
    @media (max-width: 576px) {
        .share-social-buttons .btn {
            min-width: 100px;
            font-size: 0.85rem;
        }
        
        .qr-code-container {
            width: 180px;
            height: 180px;
        }
    }
</style>

<!-- Исправленный JavaScript для работы с шарингом и генерацией QR-кода -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем доступность API Share для нативного шаринга
    if (navigator.share) {
        document.getElementById('native-share-container').style.display = 'block';
        
        // Настраиваем обработчик для нативного шаринга
        document.getElementById('native-share-btn').addEventListener('click', function() {
            const profileUrl = document.getElementById('share-profile-url').value;
            const profileTitle = document.title || 'Мой профиль';
            
            navigator.share({
                title: profileTitle,
                text: 'Посмотрите мой профиль с шаблонами',
                url: profileUrl
            })
            .then(() => {
                console.log('Успешный шаринг');
                closeModalPanel('share-profile-modal');
            })
            .catch(error => console.log('Ошибка шаринга:', error));
        });
    }
    
    // Копирование ссылки с использованием Clipboard API или fallback
    document.getElementById('copy-profile-url').addEventListener('click', function() {
        const urlField = document.getElementById('share-profile-url');
        const profileUrl = urlField.value;
        
        // Используем современный Clipboard API, если доступен
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(profileUrl)
                .then(() => {
                    showCopySuccess(this, urlField);
                })
                .catch(err => {
                    console.error('Не удалось скопировать: ', err);
                    fallbackCopyToClipboard(urlField);
                });
        } else {
            fallbackCopyToClipboard(urlField);
        }
    });
    
    // Скачивание QR-кода
    document.getElementById('download-qr-code').addEventListener('click', function() {
        const qrContainer = document.getElementById('profile-qr-code');
        const qrImage = qrContainer.querySelector('img');
        
        if (!qrImage) {
            console.error('QR код еще не сгенерирован');
            return;
        }
        
        // Создаем временную ссылку для скачивания
        const a = document.createElement('a');
        a.href = qrImage.src;
        a.download = 'profile-qr-code.png';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });
    
    // При открытии модального окна
    const shareModal = document.getElementById('share-profile-modal');
    if (shareModal) {
        shareModal.addEventListener('show.modal-panel', function() {
            setTimeout(() => {
                createQRCode();
            }, 300); // Небольшая задержка для гарантированной загрузки модального окна
        });
    }
});

// Fallback для копирования в буфер обмена
function fallbackCopyToClipboard(inputElement) {
    inputElement.select();
    inputElement.setSelectionRange(0, 99999); // Для мобильных устройств
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(document.getElementById('copy-profile-url'), inputElement);
        } else {
            console.error('Не удалось скопировать текст');
        }
    } catch (err) {
        console.error('Ошибка при копировании текста: ', err);
    }
}

// Показываем уведомление об успешном копировании
function showCopySuccess(buttonElement, inputElement) {
    // Визуальный эффект успешного копирования
    inputElement.classList.add('copy-success');
    const originalButtonHTML = buttonElement.innerHTML;
    buttonElement.innerHTML = '<i class="bi bi-check"></i> Скопировано';
    
    setTimeout(() => {
        inputElement.classList.remove('copy-success');
        buttonElement.innerHTML = originalButtonHTML;
    }, 2000);
}

// Функция для генерации QR-кода профиля (переименована)
function createQRCode() {
    const profileUrl = document.getElementById('share-profile-url').value;
    const qrContainer = document.getElementById('profile-qr-code');
    
    // Очищаем контейнер и показываем спиннер загрузки
    qrContainer.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Загрузка...</span></div>';
    
    // Используем Google Charts API для генерации QR-кода (более надежный вариант)
    try {
        const googleChartUrl = `https://chart.googleapis.com/chart?cht=qr&chl=${encodeURIComponent(profileUrl)}&chs=200x200&choe=UTF-8&chld=L|2`;
        
        // Создаем изображение
        const qrImage = new Image();
        qrImage.src = googleChartUrl;
        qrImage.alt = 'QR код для профиля';
        
        // Когда изображение загружено, добавляем его в контейнер
        qrImage.onload = function() {
            qrContainer.innerHTML = '';
            qrContainer.appendChild(qrImage);
        };
        
        // При ошибке загрузки изображения
        qrImage.onerror = function() {
            fallbackQRCode(profileUrl, qrContainer);
        };
        
    } catch (error) {
        console.error('Ошибка при создании QR кода через Google Charts API', error);
        fallbackQRCode(profileUrl, qrContainer);
    }
}

// Резервный метод создания QR-кода
function fallbackQRCode(url, container) {
    // Проверяем доступность API Web API Canvas
    if (!window.CanvasRenderingContext2D) {
        // Если Canvas недоступен, показываем ссылку
        container.innerHTML = `
            <div class="alert alert-warning">
                Не удалось создать QR-код. Используйте эту ссылку:
                <br>
                <a href="${url}" target="_blank" class="mt-2 d-block">${url}</a>
            </div>
        `;
        return;
    }
    
    // Пробуем использовать API QRServer.com
    const qrServerUrl = `https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(url)}&size=200x200&format=svg`;
    
    const qrImage = new Image();
    qrImage.src = qrServerUrl;
    qrImage.alt = 'QR код для профиля';
    
    qrImage.onload = function() {
        container.innerHTML = '';
        container.appendChild(qrImage);
    };
    
    qrImage.onerror = function() {
        container.innerHTML = `
            <div class="alert alert-warning">
                Не удалось создать QR-код. Используйте эту ссылку:
                <br>
                <a href="${url}" target="_blank" class="mt-2 d-block">${url}</a>
            </div>
        `;
    };
}

// Функция для шаринга в социальные сети
function shareProfile(platform) {
    const profileUrl = document.getElementById('share-profile-url').value;
    const profileTitle = document.title || 'Мой профиль';
    let shareUrl = '';
    
    switch(platform) {
        case 'vk':
            shareUrl = `https://vk.com/share.php?url=${encodeURIComponent(profileUrl)}`;
            break;
            
        case 'telegram':
            shareUrl = `https://t.me/share/url?url=${encodeURIComponent(profileUrl)}&text=${encodeURIComponent(profileTitle)}`;
            break;
            
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(profileTitle + ' ' + profileUrl)}`;
            break;
            
        case 'email':
            window.location.href = `mailto:?subject=${encodeURIComponent(profileTitle)}&body=${encodeURIComponent('Посмотрите мой профиль с шаблонами: ' + profileUrl)}`;
            return; // Для email используется mailto:, поэтому выходим из функции
    }
    
    // Открываем окно шаринга
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=640,height=480');
    }
    
    // Закрываем модальное окно через небольшую задержку
    setTimeout(() => {
        closeModalPanel('share-profile-modal');
    }, 500);
}
</script>
  
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-share.blade.php ENDPATH**/ ?>