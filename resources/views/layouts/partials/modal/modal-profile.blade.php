<!-- Модальное окно профиля пользователя -->
<div class="modal-panel fade" id="user-profile-modal">
    <div class="modal-backdrop"></div>
    <div class="modal-panel-dialog">
        <div class="modal-panel-content">
            
          
            <div class="modal-panel-body">
                <div class="text-center mb-4">
                    <div class="avatar-upload-container position-relative mx-auto" style="">
                        <img id="profile-avatar-preview" 
                            src="{{ Auth::user()->avatar ? asset('storage/avatars/'.Auth::user()->avatar) : asset('images/default-avatar.jpg') }}" 
                            class="profile-avatar  w-100 h-100" 
                            alt="Аватар пользователя"
                            style="object-fit: cover;">
                            
                        <div class="avatar-overlay  d-flex align-items-center justify-content-center">
                            <i class="bi bi-camera"></i>
                            <input type="file" id="avatar-upload" class="position-absolute opacity-0 w-100 h-100 top-0 start-0" 
                                style="cursor: pointer;" accept="image/*">
                        </div>
                    </div>
                    
                    <!-- Информация о балансе SUP если есть -->
                    @php
                        $supBalance = Auth::user()->supBalance ? Auth::user()->supBalance->amount : 0;
                    @endphp
                    <p class="badge bg-primary mt-2" id="user-sup-balance">SUP: {{ $supBalance }}</p>
                </div>

                <!-- Форма обновления профиля -->
                <form id="profile-update-form" action="{{ route('user.update-profile') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="profile-name" class="form-label">Имя</label>
                        <input type="text" class="form-control" id="profile-name" 
                            name="name" value="{{ Auth::user()->name }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="profile-email" 
                            name="email" value="{{ Auth::user()->email }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile-phone" class="form-label">Телефон</label>
                        <input type="tel" class="form-control maskphone" id="profile-phone" 
                            name="phone" value="{{ Auth::user()->phone ?? '' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile-birth-date" class="form-label">Дата рождения</label>
                        <input type="date" class="form-control" id="profile-birth-date" 
                            name="birth_date" value="{{ Auth::user()->birth_date ? \Carbon\Carbon::parse(Auth::user()->birth_date)->format('Y-m-d') : '' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Пол</label>
                        <div class="d-flex">
                            <div class="form-check me-4">
                                <input class="form-check-input" type="radio" name="gender" id="gender-male" 
                                    value="male" {{ (Auth::user()->gender ?? '') == 'male' ? 'checked' : '' }}>
                                <label class="form-check-label" for="gender-male">
                                    Мужской
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender-female" 
                                    value="female" {{ (Auth::user()->gender ?? '') == 'female' ? 'checked' : '' }}>
                                <label class="form-check-label" for="gender-female">
                                    Женский
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="mb-3">
                        <h5>Изменение пароля</h5>
                        <div class="mb-3">
                            <label for="current-password" class="form-label">Текущий пароль</label>
                            <input type="password" class="form-control" id="current-password" name="current_password">
                            <div class="form-text">Оставьте поля пустыми, если не хотите менять пароль</div>
                        </div>
                        <div class="mb-3">
                            <label for="new-password" class="form-label">Новый пароль</label>
                            <input type="password" class="form-control" id="new-password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="password-confirm" class="form-label">Подтверждение пароля</label>
                            <input type="password" class="form-control" id="password-confirm" name="password_confirmation">
                        </div>
                    </div>

                    <div id="avatar-update-form">
                        <input type="hidden" name="avatar_updated" value="0" id="avatar-updated-field">
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

                <!-- Кнопка выхода из аккаунта -->
                <div class="text-center mt-4 pt-3 border-top">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right me-1"></i> Выйти из аккаунта
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript для работы с профилем пользователя -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Код для работы с модальным окном профиля
        const profileModal = document.getElementById('user-profile-modal');
        if (profileModal) {
            // Загружаем баланс SUP при открытии модального окна
            profileModal.addEventListener('show.modal-panel', function() {
                loadSupBalance();
            });

            // Обработчик загрузки аватара
            const avatarUpload = document.getElementById('avatar-upload');
            const avatarPreview = document.getElementById('profile-avatar-preview');
            const avatarContainer = document.querySelector('.avatar-upload-container');
            const avatarUpdatedField = document.getElementById('avatar-updated-field');
            
            // Флаг для предотвращения множественных вызовов диалога выбора файла
            let fileDialogOpen = false;

            if (avatarUpload && avatarPreview && avatarContainer) {
                // Добавляем возможность клика по аватару для выбора файла
                avatarContainer.addEventListener('click', function(e) {
                    // Предотвращаем всплытие события
                    e.stopPropagation();
                    
                    // Предотвращаем многократные вызовы
                    if (fileDialogOpen) return;
                    
                    fileDialogOpen = true;
                    avatarUpload.click();
                    
                    // Сбрасываем флаг через небольшую задержку
                    setTimeout(() => {
                        fileDialogOpen = false;
                    }, 1000);
                });
                
                // Предотвращаем всплытие клика с инпута выбора файла
                avatarUpload.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                // Обработчик изменения файла с поддержкой сжатия
                avatarUpload.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        const file = e.target.files[0];
                        
                        // Проверяем тип файла
                        if (!file.type.match('image.*')) {
                            showToast('Выберите изображение', 'error');
                            return;
                        }
                        
                        // Сжимаем и отображаем изображение
                        compressImage(file, function(compressedImage, compressedBlob) {
                            // Устанавливаем сжатое изображение для предпросмотра
                            avatarPreview.src = compressedImage;
                            
                            // Устанавливаем флаг обновления аватара
                            if (avatarUpdatedField) {
                                avatarUpdatedField.value = '1';
                            }
                            
                            // Создаем скрытое поле для передачи Base64 данных
                            let base64Field = document.getElementById('avatar-base64');
                            if (!base64Field) {
                                base64Field = document.createElement('input');
                                base64Field.type = 'hidden';
                                base64Field.id = 'avatar-base64';
                                base64Field.name = 'avatar_base64';
                                document.getElementById('avatar-update-form').appendChild(base64Field);
                            }
                            base64Field.value = compressedImage;
                            
                            // Показываем сообщение о сжатии
                            const originalSize = Math.round(file.size / 1024);
                            const newSize = Math.round(compressedBlob.size / 1024);
                            
                            if (newSize < originalSize) {
                                showToast(`Изображение сжато: ${originalSize} KB → ${newSize} KB`, 'success');
                            }
                        });
                    }
                });
            }

            // Обработчик отправки формы
            const profileUpdateForm = document.getElementById('profile-update-form');
            if (profileUpdateForm) {
                profileUpdateForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const saveButton = document.getElementById('save-profile-btn');

                    // Блокируем кнопку на время отправки
                    saveButton.disabled = true;
                    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохранение...';

                    // Отправляем AJAX-запрос
                    fetch(profileUpdateForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        if (!response.ok) {
                            // Если статус не OK (200-299), создаем ошибку
                            return response.json().then(data => {
                                throw new Error(data.message || `Ошибка статуса HTTP: ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Разблокируем кнопку
                        saveButton.disabled = false;
                        saveButton.innerHTML = 'Сохранить';
                        
                        if (data.success) {
                            // Обновляем аватар на странице, если он был изменен и возвращен сервером
                            if (data.user && data.user.avatar) {
                                const avatarElements = document.querySelectorAll('.user-avatar');
                                const newAvatarUrl = data.user.avatar;
                                
                                avatarElements.forEach(element => {
                                    element.src = newAvatarUrl;
                                });
                                
                                // Также обновляем аватар в мобильной навигации, если есть
                                const mobileNavAvatar = document.querySelector('.mobile-nav-user-avatar');
                                if (mobileNavAvatar) {
                                    mobileNavAvatar.src = newAvatarUrl;
                                }
                            }
                            
                            // Показываем уведомление об успешном обновлении
                            showToast('Профиль успешно обновлен', 'success');
                            
                            // Закрываем модальное окно
                            closeModalPanel('user-profile-modal');
                            
                            // Обновляем страницу для отображения изменений с небольшой задержкой
                            setTimeout(() => {
                                window.location.reload();
                            }, 800);
                        } else {
                            // Показываем сообщение об ошибке
                            showToast(data.message || 'Произошла ошибка при обновлении профиля', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка при обновлении профиля:', error);
                        
                        // Разблокируем кнопку
                        saveButton.disabled = false;
                        saveButton.innerHTML = 'Сохранить';
                        
                        // Показываем детальную ошибку
                        showToast(`Ошибка: ${error.message || 'Произошла ошибка при обновлении профиля'}`, 'error');
                    });
                });
            }
        }

        // Функция для сжатия изображения с использованием Canvas API
        function compressImage(file, callback, maxWidth = 500, maxHeight = 500, quality = 0.7) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    // Определяем размеры для холста, сохраняя пропорции
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > height) {
                        if (width > maxWidth) {
                            height *= maxWidth / width;
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width *= maxHeight / height;
                            height = maxHeight;
                        }
                    }
                    
                    // Создаем холст для сжатия
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    
                    // Отрисовываем изображение на холсте
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // Конвертируем холст в Data URL
                    const dataUrl = canvas.toDataURL('image/jpeg', quality);
                    
                    // Конвертируем Data URL в Blob для загрузки на сервер
                    canvas.toBlob(
                        (blob) => callback(dataUrl, blob),
                        'image/jpeg',
                        quality
                    );
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
        
        // Функция для замены файла в элементе input
        function replaceFileInInput(inputElement, blob, fileName, fileType) {
            // Создаем новый File объект из Blob
            const newFile = new File([blob], fileName, {
                type: fileType,
                lastModified: new Date().getTime()
            });

            // Создаем новый объект DataTransfer для эмуляции FileList
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(newFile);
            
            // Устанавливаем новый FileList в элемент input
            inputElement.files = dataTransfer.files;
        }

        // Функция для загрузки баланса SUP
        function loadSupBalance() {
            const balanceElement = document.getElementById('user-sup-balance');

            // Проверяем существование элемента перед запросом
            if (!balanceElement) {
                console.warn('Элемент user-sup-balance не найден в DOM');
                return; // Выходим из функции если элемента нет
            }

            fetch('/sup/balance')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    // Проверяем тип контента перед парсингом
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error(`Неверный формат ответа! Ожидался JSON, получен: ${contentType}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (balanceElement) { // Повторная проверка на случай удаления элемента
                        balanceElement.textContent = `Баланс SUP: ${data.formatted_balance}`;
                    }
                })
                .catch(error => {
                    console.error('Ошибка при загрузке баланса SUP:', error);
                    if (balanceElement) {
                        balanceElement.textContent = 'Ошибка загрузки баланса';
                    }
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

            // Показываем уведомление
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            // Удаляем уведомление через 3 секунды
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
        
        // Добавляем маску телефона для поля в профиле
        const phoneInput = document.getElementById('profile-phone');
        if (phoneInput) {
            phoneInput.addEventListener("input", maskPhone);
            phoneInput.addEventListener("focus", maskPhone);
            phoneInput.addEventListener("blur", maskPhone);
            
            // Применяем маску к существующему номеру при загрузке
            if (phoneInput.value) {
                maskPhone.call(phoneInput, {type: 'input'});
            }
        }
        
        function maskPhone(event) {
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
    });
</script>

<!-- Стили для модального окна профиля -->
<style>
    /* Основные стили для модального окна */
    .profile-avatar-container {
        position: relative;
        border-radius: 50%;
        overflow: hidden;
        width: 120px;
        height: 120px;
        margin: 0 auto;
    }

    .avatar-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s;
    }

    .avatar-upload-container:hover .avatar-overlay {
        opacity: 1;
    }

    .avatar-overlay i {
        color: white;
        font-size: 24px;
    }
    
    /* Стили для уведомлений */
    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background-color: #6c8aec;
        color: white;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        z-index: 1080;
        opacity: 0;
        transform: translateY(-20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
        max-width: 80%;
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
    
    /* Новые стили для модального окна */
    .modal-panel {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1050;
        display: none;
        overflow: hidden;
        outline: 0;
    }
    
    .modal-panel.fade .modal-panel-dialog {
        transition: transform 0.3s ease-out;
        transform: translate(0, -25%);
    }
    
    .modal-panel.show .modal-panel-dialog {
        transform: translate(0, 0);
    }
    
    .modal-backdrop {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1040;
        background-color: rgba(0, 0, 0, 0.5);
        opacity: 0;
        transition: opacity 0.15s linear;
    }
    
    .modal-panel.fade .modal-backdrop {
        opacity: 0;
    }
    
    .modal-panel.show .modal-backdrop {
        opacity: 1;
    }
    
    .modal-panel_dialog {
        position: relative;
        width: auto;
        margin: 0.5rem;
        pointer-events: none;
        max-width: 500px;
        margin: 30px auto;
    }
    
    .modal-panel-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        pointer-events: auto;
        background-color: #fff;
        background-clip: padding-box;
        border-radius: 0.5rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
        outline: 0;
    }
    
    .modal-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #eaedf2;
    }
    
    .modal-panel-body {
        position: relative;
        flex: 1 1 auto;
        padding: 1.25rem;
        overflow-y: auto;
    }
    
    /* Адаптивные стили для модальных окон на мобильных устройствах */
    @media (max-width: 767px) {
        .modal-panel.fade .modal-panel_dialog {
            transform: translate(0, 100%);
        }
        
        .modal-panel.show .modal-panel_dialog {
            transform: translate(0, 0);
        }
        
        .modal-panel_dialog {
            max-width: none;
            height: 100%;
            margin: 0;
        }
        
        .modal-panel-content {
            height: 100%;
            border-radius: 0;
            display: flex;
            flex-direction: column;
        }
        
        .modal-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .modal-panel-header {
            padding: 0.75rem 1rem;
        }
        
        .modal-panel-header .btn-close {
            padding: 0.5rem;
        }
    }
</style>
