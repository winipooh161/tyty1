<!-- Модальное окно профиля пользователя -->
<div class="modal-panel fade" id="user-profile-modal">
    <div class="modal-backdrop"></div>
    <div class="modal-panel-dialog">
        <div class="modal-panel-content">
            
            
            <div class="modal-panel-body">
                <div class="text-center mb-4">
                    <div class="avatar-upload-container position-relative mx-auto" style="">
                        <img id="profile-avatar-preview" 
                            src="<?php echo e(Auth::user()->avatar ? asset('storage/avatars/'.Auth::user()->avatar) : asset('images/default-avatar.jpg')); ?>" 
                            class="profile-avatar rounded-circle w-100 h-100" 
                            alt="Аватар пользователя"
                            style="object-fit: cover;">
                            
                        <div class="avatar-overlay rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-camera"></i>
                            <input type="file" id="avatar-upload" class="position-absolute opacity-0 w-100 h-100 top-0 start-0" 
                                style="cursor: pointer;" accept="image/*">
                        </div>
                    </div>
                    
                    <!-- Информация о балансе SUP если есть -->
                    <?php
                        $supBalance = Auth::user()->supBalance ? Auth::user()->supBalance->amount : 0;
                    ?>
                    <p class="badge bg-primary mt-2" id="user-sup-balance">Баланс SUP: <?php echo e($supBalance); ?></p>
                </div>

                <!-- Форма обновления профиля -->
                <form id="profile-update-form" action="<?php echo e(route('user.update-profile')); ?>" method="POST"
                    enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label for="profile-name" class="form-label">Имя</label>
                        <input type="text" class="form-control" id="profile-name" 
                            name="name" value="<?php echo e(Auth::user()->name); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="profile-email" 
                            name="email" value="<?php echo e(Auth::user()->email); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile-phone" class="form-label">Телефон</label>
                        <input type="tel" class="form-control" id="profile-phone" 
                            name="phone" value="<?php echo e(Auth::user()->phone ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile-birth-date" class="form-label">Дата рождения</label>
                        <input type="date" class="form-control" id="profile-birth-date" 
                            name="birth_date" value="<?php echo e(Auth::user()->birth_date ? \Carbon\Carbon::parse(Auth::user()->birth_date)->format('Y-m-d') : ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Пол</label>
                        <div class="d-flex">
                            <div class="form-check me-4">
                                <input class="form-check-input" type="radio" name="gender" id="gender-male" 
                                    value="male" <?php echo e((Auth::user()->gender ?? '') == 'male' ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="gender-male">
                                    Мужской
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender-female" 
                                    value="female" <?php echo e((Auth::user()->gender ?? '') == 'female' ? 'checked' : ''); ?>>
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

                    <div id="avatar-update-form" class="d-none">
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

            if (avatarUpload && avatarPreview && avatarContainer) {
                // Добавляем возможность клика по аватару для выбора файла
                avatarContainer.addEventListener('click', function() {
                    avatarUpload.click();
                });
                
                // Обработчик изменения файла
                avatarUpload.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        const file = e.target.files[0];
                        
                        // Проверяем размер файла (не более 2 МБ)
                        if (file.size > 2 * 1024 * 1024) {
                            showToast('Файл слишком большой. Максимальный размер: 2 МБ', 'error');
                            return;
                        }
                        
                        // Проверяем тип файла
                        if (!file.type.match('image.*')) {
                            showToast('Выберите изображение', 'error');
                            return;
                        }
                        
                        // Создаем URL для предпросмотра
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            avatarPreview.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
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
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Разблокируем кнопку
                        saveButton.disabled = false;
                        saveButton.innerHTML = 'Сохранить';
                        
                        if (data.success) {
                            // Показываем уведомление об успешном обновлении
                            showToast('Профиль успешно обновлен', 'success');
                            
                            // Закрываем модальное окно
                            closeModalPanel('user-profile-modal');
                            
                            // Перенаправляем на страницу my-templates после короткой задержки
                            setTimeout(() => {
                                window.location.href = '/client/my-templates';
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
                        
                        showToast('Произошла ошибка при обновлении профиля', 'error');
                    });
                });
            }
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

            // Удаляем уведомление через 3 секунды
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
    });
</script>

<!-- Стили для модального окна профиля -->
<style>
    /* Стили для модального окна профиля */
    .profile-avatar-container {
        position: relative;
        border-radius: 50%;
        overflow: hidden;
    }

    .avatar-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
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
        background-color: #28a745;
        color: white;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        z-index: 1080;
        opacity: 1;
        transition: opacity 0.3s;
        max-width: 80%;
        font-size: 14px;
        font-weight: 500;
    }

    .toast-notification.error {
        background-color: #dc3545;
    }

    /* Анимация появления уведомлений */
    @keyframes toastFadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .toast-notification {
        animation: toastFadeIn 0.3s ease-out;
    }
</style>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/layouts/partials/modal/modal-profile.blade.php ENDPATH**/ ?>