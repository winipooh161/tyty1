<script>
document.addEventListener('DOMContentLoaded', function() {
    // Основные элементы редактора
    const mediaFile = document.getElementById('mediaFile');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadSection = document.getElementById('uploadSection');
    const imageEditorSection = document.getElementById('imageEditorSection');
    const videoEditorSection = document.getElementById('videoEditorSection');
    const actionButtons = document.getElementById('actionButtons');
    const saveBtn = document.getElementById('saveBtn');
    const processingIndicator = document.getElementById('processingIndicator');
    
    // Элементы для редактирования изображений
    const imagePreview = document.getElementById('imagePreview');
    const imageViewport = document.getElementById('imageViewport');
    
    // Элементы для редактирования видео
    const videoPreview = document.getElementById('videoPreview');
    const videoTimeline = document.getElementById('videoTimeline');
    const trimDuration = document.getElementById('trimDuration');
    const startTimeDisplay = document.getElementById('startTimeDisplay');
    const endTimeDisplay = document.getElementById('endTimeDisplay');
    const durationBadge = document.getElementById('durationBadge');
    const previewVideoBtn = document.getElementById('previewVideoBtn');
    
    // Переменные для хранения данных
    let currentFile = null;
    let fileType = null;
    let originalVideo = null;
    let currentScale = 1;
    let currentTranslateX = 0;
    let currentTranslateY = 0;
    let currentRotation = 0;
    let videoStartTime = 0;
    let videoEndTime = 15;
    
    // Получаем ID шаблона, если он был передан
    const templateId = document.getElementById('templateId')?.value;
    
    // Инициализация редактора
    function init() {
        // Обработчики событий для кнопок
        uploadBtn.addEventListener('click', () => mediaFile.click());
        mediaFile.addEventListener('change', handleFileSelect);
        saveBtn.addEventListener('click', processMedia);
        
        // Добавляем поддержку drag-n-drop для загрузки файла
        setupDragAndDrop();
        
        // Инициализация редактирования изображения
        setupImageEditor();
        
        // Инициализация редактирования видео
        setupVideoEditor();
        
        // Скрываем навигационную панель для полноэкранного режима
        const mbNavigation = document.querySelector('.mb-navigation');
        if (mbNavigation) {
            mbNavigation.style.display = 'none';
        }
    }
    
    // Настройка drag-n-drop для загрузки файлов
    function setupDragAndDrop() {
        const dropZone = document.querySelector('.media-editor-container');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropZone.classList.add('highlight');
        }
        
        function unhighlight() {
            dropZone.classList.remove('highlight');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                mediaFile.files = files;
                handleFileSelect();
            }
        }
    }
    
    // Настройка редактора изображений с улучшенным управлением жестами
    function setupImageEditor() {
        // Настройка перетаскивания и масштабирования изображения
        let isDragging = false;
        let startX, startY, startTranslateX, startTranslateY;
        let lastTouchDistance = 0;
        let lastTouchAngle = 0;
        
        // Добавляем обработчики событий для перетаскивания
        imagePreview.addEventListener('pointerdown', startDrag);
        window.addEventListener('pointermove', drag);
        window.addEventListener('pointerup', endDrag);
        window.addEventListener('pointercancel', endDrag);
        
        // Добавляем обработчики для жестов масштабирования
        imagePreview.addEventListener('touchstart', handleTouchStart, { passive: false });
        imagePreview.addEventListener('touchmove', handleTouchMove, { passive: false });
        
        // Обработчик начала перетаскивания
        function startDrag(e) {
            // Если это множественное касание, не начинаем перетаскивание
            if (e.pointerType === 'touch' && e.isPrimary === false) return;
            
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startTranslateX = currentTranslateX;
            startTranslateY = currentTranslateY;
            
            // Устанавливаем захват указателя
            e.target.setPointerCapture(e.pointerId);
            e.preventDefault();
        }
        
        // Обработчик перетаскивания
        function drag(e) {
            if (!isDragging || e.pointerType === 'touch' && !e.isPrimary) return;
            
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            currentTranslateX = startTranslateX + deltaX;
            currentTranslateY = startTranslateY + deltaY;
            
            updateImageTransform();
        }
        
        // Обработчик завершения перетаскивания
        function endDrag(e) {
            if (e.pointerType === 'touch' && !e.isPrimary) return;
            
            if (isDragging) {
                isDragging = false;
                // Освобождаем захват указателя
                if (e.target.releasePointerCapture) {
                    e.target.releasePointerCapture(e.pointerId);
                }
            }
        }
        
        // Обработчик начала касания для жестов
        function handleTouchStart(e) {
            if (e.touches.length === 2) {
                // Запоминаем начальное расстояние и угол между касаниями
                const touch1 = e.touches[0];
                const touch2 = e.touches[1];
                lastTouchDistance = getTouchDistance(touch1, touch2);
                lastTouchAngle = getTouchAngle(touch1, touch2);
                
                e.preventDefault();
            }
        }
        
        // Обработчик движения для жестов
        function handleTouchMove(e) {
            if (e.touches.length === 2) {
                const touch1 = e.touches[0];
                const touch2 = e.touches[1];
                
                // Вычисляем текущее расстояние и угол между касаниями
                const currentDistance = getTouchDistance(touch1, touch2);
                const currentAngle = getTouchAngle(touch1, touch2);
                
                // Масштабирование на основе изменения расстояния
                if (lastTouchDistance > 0) {
                    const scaleFactor = currentDistance / lastTouchDistance;
                    currentScale *= scaleFactor;
                    currentScale = Math.max(0.5, Math.min(currentScale, 5)); // Ограничиваем масштаб
                }
                
                // Вращение на основе изменения угла
                if (Math.abs(currentAngle - lastTouchAngle) < 30) {
                    currentRotation += (currentAngle - lastTouchAngle);
                }
                
                // Обновляем сохраненные значения
                lastTouchDistance = currentDistance;
                lastTouchAngle = currentAngle;
                
                updateImageTransform();
                e.preventDefault();
            }
        }
        
        // Вычисление расстояния между двумя точками касания
        function getTouchDistance(touch1, touch2) {
            const dx = touch1.clientX - touch2.clientX;
            const dy = touch1.clientY - touch2.clientY;
            return Math.sqrt(dx * dx + dy * dy);
        }
        
        // Вычисление угла между двумя точками касания
        function getTouchAngle(touch1, touch2) {
            return Math.atan2(
                touch2.clientY - touch1.clientY,
                touch2.clientX - touch1.clientX
            ) * (180 / Math.PI);
        }
    }
    
    // Обновление трансформации изображения
    function updateImageTransform() {
        imagePreview.style.transform = `translate(${currentTranslateX}px, ${currentTranslateY}px) scale(${currentScale}) rotate(${currentRotation}deg)`;
    }
    
    // Настройка редактора видео в стиле рилсов
    function setupVideoEditor() {
        // Элементы управления видео
        const leftHandle = document.querySelector('.left-handle');
        const rightHandle = document.querySelector('.right-handle');
        const trimWindow = document.querySelector('.video-trim-window');
        const timelineCursor = document.querySelector('.timeline-cursor');
        
        let isDraggingLeft = false;
        let isDraggingRight = false;
        let timelineWidth = 0;
        let videoDuration = 0;
        
        // Обработчик загрузки метаданных видео
        videoPreview.addEventListener('loadedmetadata', () => {
            videoDuration = videoPreview.duration;
            videoEndTime = Math.min(videoDuration, 15); // Ограничиваем максимальной длиной 15 секунд
            updateTimeDisplays();
            generateVideoThumbnails();
        });
        
        // Обработчик обновления времени видео
        videoPreview.addEventListener('timeupdate', () => {
            const currentTime = videoPreview.currentTime;
            
            // Обновляем положение курсора на таймлайне
            const position = (currentTime / videoDuration) * 100;
            if (timelineCursor) {
                timelineCursor.style.left = `${position}%`;
            }
            
            // Если вышли за границы выбранного отрезка
            if (currentTime < videoStartTime) {
                videoPreview.currentTime = videoStartTime;
            } else if (currentTime > videoEndTime) {
                videoPreview.pause();
                videoPreview.currentTime = videoStartTime; // Возвращаемся к началу отрезка
            }
        });
        
        // Обработчик клика на таймлайн - перемещение к этой позиции
        if (videoTimeline) {
            videoTimeline.addEventListener('click', (e) => {
                const rect = videoTimeline.getBoundingClientRect();
                const position = (e.clientX - rect.left) / rect.width;
                videoPreview.currentTime = position * videoDuration;
            });
        }
        
        // Настройка перетаскивания левой ручки
        if (leftHandle) {
            leftHandle.addEventListener('pointerdown', (e) => {
                isDraggingLeft = true;
                e.preventDefault();
                leftHandle.setPointerCapture(e.pointerId);
            });
            
            leftHandle.addEventListener('pointermove', (e) => {
                if (!isDraggingLeft) return;
                
                const rect = videoTimeline.getBoundingClientRect();
                timelineWidth = rect.width;
                const position = Math.max(0, Math.min((e.clientX - rect.left) / timelineWidth, 0.9));
                
                // Обновляем начальное время и позицию левой ручки
                videoStartTime = position * videoDuration;
                // Проверяем, чтобы отрезок не был больше 15 секунд
                if (videoEndTime - videoStartTime > 15) {
                    videoEndTime = videoStartTime + 15;
                }
                
                // Обновляем положение ручки и размер окна обрезки
                const rightPosition = (videoEndTime / videoDuration) * 100;
                const leftPosition = (videoStartTime / videoDuration) * 100;
                
                trimWindow.style.left = `${leftPosition}%`;
                trimWindow.style.width = `${rightPosition - leftPosition}%`;
                
                updateTimeDisplays();
                videoPreview.currentTime = videoStartTime;
            });
            
            leftHandle.addEventListener('pointerup', (e) => {
                if (isDraggingLeft) {
                    isDraggingLeft = false;
                    leftHandle.releasePointerCapture(e.pointerId);
                }
            });
            
            leftHandle.addEventListener('pointercancel', (e) => {
                if (isDraggingLeft) {
                    isDraggingLeft = false;
                    leftHandle.releasePointerCapture(e.pointerId);
                }
            });
        }
        
        // Настройка перетаскивания правой ручки
        if (rightHandle) {
            rightHandle.addEventListener('pointerdown', (e) => {
                isDraggingRight = true;
                e.preventDefault();
                rightHandle.setPointerCapture(e.pointerId);
            });
            
            rightHandle.addEventListener('pointermove', (e) => {
                if (!isDraggingRight) return;
                
                const rect = videoTimeline.getBoundingClientRect();
                timelineWidth = rect.width;
                const position = Math.min(1, Math.max((e.clientX - rect.left) / timelineWidth, 0.1));
                
                // Обновляем конечное время и позицию правой ручки
                videoEndTime = position * videoDuration;
                // Проверяем, чтобы отрезок не был больше 15 секунд
                if (videoEndTime - videoStartTime > 15) {
                    videoEndTime = videoStartTime + 15;
                }
                
                // Обновляем положение ручки и размер окна обрезки
                const rightPosition = (videoEndTime / videoDuration) * 100;
                const leftPosition = (videoStartTime / videoDuration) * 100;
                
                trimWindow.style.width = `${rightPosition - leftPosition}%`;
                
                updateTimeDisplays();
            });
            
            rightHandle.addEventListener('pointerup', (e) => {
                if (isDraggingRight) {
                    isDraggingRight = false;
                    rightHandle.releasePointerCapture(e.pointerId);
                }
            });
            
            rightHandle.addEventListener('pointercancel', (e) => {
                if (isDraggingRight) {
                    isDraggingRight = false;
                    rightHandle.releasePointerCapture(e.pointerId);
                }
            });
        }
        
        // Обработчик кнопки предпросмотра
        if (previewVideoBtn) {
            previewVideoBtn.addEventListener('click', () => {
                videoPreview.currentTime = videoStartTime;
                videoPreview.play();
            });
        }
        
        // Функция для генерации миниатюр видео на таймлайне
        function generateVideoThumbnails() {
            if (!videoTimeline || !videoPreview.duration) return;
            
            videoTimeline.innerHTML = '';
            
            // Определяем количество миниатюр
            const numThumbnails = Math.min(10, Math.ceil(videoPreview.duration));
            
            for (let i = 0; i < numThumbnails; i++) {
                const thumbnail = document.createElement('div');
                thumbnail.className = 'video-thumbnail';
                
                // Добавляем миниатюру в таймлайн
                videoTimeline.appendChild(thumbnail);
                
                // Создаем временный элемент canvas для получения кадра видео
                const canvas = document.createElement('canvas');
                const time = (i / numThumbnails) * videoPreview.duration;
                
                // Устанавливаем временную позицию видео для получения кадра
                videoPreview.currentTime = time;
                
                // Читаем кадр после того, как видео перейдет к указанному времени
                videoPreview.addEventListener('seeked', function seekListener() {
                    // Удаляем слушатель, чтобы не срабатывал повторно
                    videoPreview.removeEventListener('seeked', seekListener);
                    
                    // Настраиваем canvas и получаем кадр из видео
                    canvas.width = videoPreview.videoWidth / 10;
                    canvas.height = videoPreview.videoHeight / 10;
                    
                    // Рисуем кадр на canvas
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(videoPreview, 0, 0, canvas.width, canvas.height);
                    
                    // Устанавливаем миниатюру как фон элемента
                    try {
                        const dataUrl = canvas.toDataURL('image/jpeg', 0.5);
                        thumbnail.style.backgroundImage = `url(${dataUrl})`;
                    } catch (e) {
                        console.error('Ошибка при создании миниатюры:', e);
                        // Если не удалось создать миниатюру, используем цветной фон
                        thumbnail.style.backgroundColor = `hsl(${(i * 36) % 360}, 70%, 60%)`;
                    }
                    
                    // Если это последняя миниатюра, устанавливаем время на начало
                    if (i === numThumbnails - 1) {
                        videoPreview.currentTime = videoStartTime;
                    }
                }, { once: true });
            }
        }
        
        // Функция для обновления отображения времени
        function updateTimeDisplays() {
            // Обновляем отображение выбранного отрезка
            const duration = videoEndTime - videoStartTime;
            trimDuration.textContent = `${duration.toFixed(1)} сек`;
            
            // Форматируем время для отображения
            startTimeDisplay.textContent = formatTime(videoStartTime);
            endTimeDisplay.textContent = formatTime(videoEndTime);
            
            // Обновляем индикатор максимальной длительности
            if (duration >= 15) {
                durationBadge.textContent = '15 сек (макс.)';
                durationBadge.style.backgroundColor = 'rgba(220, 53, 69, 0.7)'; // Красный для максимальной длительности
            } else {
                durationBadge.textContent = `${duration.toFixed(1)} сек`;
                durationBadge.style.backgroundColor = 'rgba(0, 123, 255, 0.7)'; // Синий для обычной длительности
            }
        }
        
        // Функция для форматирования времени в формате 0:00
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
    }
    
    // Обработка выбора файла
    function handleFileSelect() {
        if (!mediaFile.files || mediaFile.files.length === 0) return;
        
        currentFile = mediaFile.files[0];
        const fileUrl = URL.createObjectURL(currentFile);
        
        // Определяем тип файла
        if (currentFile.type.startsWith('image/')) {
            fileType = 'image';
            showImageEditor(fileUrl);
        } else if (currentFile.type.startsWith('video/')) {
            fileType = 'video';
            showVideoEditor(fileUrl);
        } else {
            alert('Неподдерживаемый тип файла. Пожалуйста, выберите изображение или видео.');
            resetEditor();
            return;
        }
        
        // Показываем кнопки действий
        uploadSection.style.display = 'none';
        actionButtons.style.display = 'flex';
    }
    
    // Показать редактор изображений
    function showImageEditor(fileUrl) {
        imagePreview.src = fileUrl;
        imageEditorSection.style.display = 'block';
        videoEditorSection.style.display = 'none';
        
        // Сбрасываем трансформацию
        currentScale = 1;
        currentTranslateX = 0;
        currentTranslateY = 0;
        currentRotation = 0;
        updateImageTransform();
    }
    
    // Показать редактор видео
    function showVideoEditor(fileUrl) {
        // Сохраняем оригинальное видео для обработки
        originalVideo = currentFile;
        
        videoPreview.src = fileUrl;
        imageEditorSection.style.display = 'none';
        videoEditorSection.style.display = 'block';
        
        // Сбрасываем переменные времени
        videoStartTime = 0;
        videoEndTime = Math.min(15, videoPreview.duration || 15);
        
        // Инициализируем таймлайн после загрузки видео
        videoPreview.onloadedmetadata = function() {
            setupVideoEditor();
        };
    }
    
    // Сброс редактора
    function resetEditor() {
        // Сбрасываем все значения
        currentFile = null;
        fileType = null;
        originalVideo = null;
        
        // Возвращаем интерфейс в исходное состояние
        uploadSection.style.display = 'flex';
        imageEditorSection.style.display = 'none';
        videoEditorSection.style.display = 'none';
        actionButtons.style.display = 'none';
        processingIndicator.style.display = 'none';
        
        // Очищаем поле файла
        mediaFile.value = '';
        
        // Сбрасываем трансформацию изображения
        currentScale = 1;
        currentTranslateX = 0;
        currentTranslateY = 0;
        currentRotation = 0;
        updateImageTransform();
    }
    
    // Обработка и сохранение медиа
    function processMedia() {
        if (!currentFile) return;
        
        // Показываем индикатор загрузки
        actionButtons.style.display = 'none';
        processingIndicator.style.display = 'flex';
        
        const formData = new FormData();
        formData.append('media_file', currentFile);
        
        // Добавляем ID шаблона, если он есть
        if (templateId) {
            formData.append('template_id', templateId);
        }
        
        // Добавляем данные о трансформации для изображений
        if (fileType === 'image') {
            const cropData = {
                scale: currentScale,
                x: currentTranslateX,
                y: currentTranslateY,
                rotation: currentRotation
            };
            formData.append('crop_data', JSON.stringify(cropData));
        }
        
        // Добавляем данные о обрезке для видео
        if (fileType === 'video') {
            formData.append('video_start', videoStartTime.toString());
            formData.append('video_end', videoEndTime.toString());
        }
        
        // Получаем CSRF токен из meta тега
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Отправляем запрос на сервер
        fetch('<?php echo e(route("media.process")); ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                // Проверяем тип содержимого ответа
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.indexOf('application/json') !== -1) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Ошибка сервера: ' + response.status);
                    });
                }
                throw new Error('Ошибка сервера: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Файл успешно обработан:', data);
                // После успешной обработки перенаправляем пользователя
                window.location.href = data.redirect_url;
            } else {
                throw new Error(data.error || 'Неизвестная ошибка');
            }
        })
        .catch(error => {
            console.error('Ошибка при отправке запроса:', error);
            
            // Показываем подробную информацию об ошибке
            processingIndicator.style.display = 'none';
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger position-fixed bottom-0 start-0 end-0 m-3';
            errorDiv.innerHTML = `
                <h5 class="alert-heading">Ошибка при обработке файла</h5>
                <p>${error.message || 'Неизвестная ошибка'}</p>
                <button type="button" class="btn btn-primary btn-sm mt-2" id="tryAgainBtn">
                    <i class="bi bi-arrow-repeat me-1"></i> Повторить
                </button>
            `;
            
            document.body.appendChild(errorDiv);
            
            // Обработчик для кнопки "Повторить"
            document.getElementById('tryAgainBtn').addEventListener('click', () => {
                errorDiv.remove();
                actionButtons.style.display = 'flex';
            });
        });
    }
    
    // Инициализация редактора
    init();
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/components/media-editor/scripts.blade.php ENDPATH**/ ?>