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
    const editorContainer = document.querySelector('.media-editor-container');
    
    // Элементы для редактирования изображений
    const imagePreview = document.getElementById('imagePreview');
    const imageViewport = document.getElementById('imageViewport');
    
    // Элементы для редактирования видео
    const videoPreview = document.getElementById('videoPreview');
    const mobileProgressBar = document.getElementById('mobileProgressBar');
    const mobileStartHandle = document.getElementById('mobileStartHandle');
    const mobileEndHandle = document.getElementById('mobileEndHandle');
    
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
    let videoDuration = 0;
    let isPlaying = false;
    
    // Получаем ID шаблона, если он был передан
    const templateId = document.getElementById('templateId')?.value;
    
    // Функция для скрытия секции загрузки
    function hideUploadSection() {
        uploadSection.style.display = 'none';
        uploadSection.style.zIndex = '-1'; // Устанавливаем отрицательный z-index для гарантированного скрытия
        console.log('Секция загрузки скрыта явно');
    }
    
    // Показать редактор изображений
    function showImageEditor(fileUrl) {
        uploadSection.style.display = 'none'; // Явно скрываем секцию загрузки
        imagePreview.src = fileUrl;
        imageEditorSection.style.display = 'block';
        videoEditorSection.style.display = 'none';
        
        // Сбрасываем трансформацию
        currentScale = 1;
        currentTranslateX = 0;
        currentTranslateY = 0;
        currentRotation = 0;
        updateImageTransform();
        
        console.log('Редактор изображений активирован');
    }
    
    // Показать редактор видео
    function showVideoEditor(fileUrl) {
        uploadSection.style.display = 'none';
        originalVideo = currentFile;
        
        videoPreview.src = fileUrl;
        imageEditorSection.style.display = 'none';
        videoEditorSection.style.display = 'block';
        
        // Сбрасываем переменные времени
        videoStartTime = 0;
        videoEndTime = 15;
        videoDuration = 0;
        
        console.log('Редактор видео активирован');
        
        // Инициализируем редактор видео
        setupVideoEditor();
    }
    
    // Обработка выбора файла
    function handleFileSelect() {
        if (!mediaFile.files || mediaFile.files.length === 0) return;
        
        currentFile = mediaFile.files[0];
        const fileUrl = URL.createObjectURL(currentFile);
        
        // Определяем тип файла
        if (currentFile.type.startsWith('image/')) {
            fileType = 'image';
            hideUploadSection(); // Гарантированное скрытие перед показом редактора
            showImageEditor(fileUrl);
        } else if (currentFile.type.startsWith('video/')) {
            fileType = 'video';
            hideUploadSection(); // Гарантированное скрытие перед показом редактора
            showVideoEditor(fileUrl);
        } else {
            alert('Неподдерживаемый тип файла. Пожалуйста, выберите изображение или видео.');
            resetEditor();
            return;
        }
        
        // Показываем кнопки действий
        actionButtons.style.display = 'flex';
        
        // Добавляем класс индикации выбора файла
        editorContainer.classList.add('file-selected');
        
        console.log('Секция загрузки скрыта, редактор активирован');
    }
    
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
    
    // Настройка видео-редактора с мобильными элементами управления
    function setupVideoEditor() {
        // Обработчик события загрузки метаданных видео
        videoPreview.addEventListener('loadedmetadata', function() {
            // Получаем и проверяем длительность видео
            videoDuration = this.duration;
            
            // Корректировка аномальных значений длительности
            if (isNaN(videoDuration) || !isFinite(videoDuration) || videoDuration <= 0 || videoDuration > 3600) {
                console.warn('Некорректная длительность видео:', videoDuration);
                videoDuration = Math.max(15, Math.min(videoDuration, 600));
            }
            
            console.log('Длительность видео:', videoDuration);
            
            // Устанавливаем начальные значения обрезки
            videoStartTime = 0;
            videoEndTime = Math.min(videoDuration, 15);
            
            // Инициализация мобильных элементов управления для обрезки видео
            setupMobileTrimControls();
            
            // Обновляем прогресс-бар
            updateProgressBar();
        });
        
        // События видеоплеера
        videoPreview.addEventListener('timeupdate', function() {
            // Если видео проигрывается и вышло за пределы выбранного диапазона,
            // останавливаем воспроизведение
            if (isPlaying && (this.currentTime < videoStartTime || this.currentTime >= videoEndTime)) {
                this.pause();
                this.currentTime = videoStartTime;
                isPlaying = false;
            }
        });
    }
    
    // Функция для обновления прогресс-бара видео
    function updateProgressBar() {
        if (!mobileProgressBar) return;
        
        // Вычисляем процентные значения для начала и конца выбранного диапазона
        const startPercent = (videoStartTime / videoDuration) * 100;
        const endPercent = (videoEndTime / videoDuration) * 100;
        
        // Устанавливаем позицию и ширину прогресс-бара
        mobileProgressBar.style.left = startPercent + '%';
        mobileProgressBar.style.width = (endPercent - startPercent) + '%';
    }
    
    // Функция настройки мобильных элементов управления для обрезки видео
    function setupMobileTrimControls() {
        if (!mobileStartHandle || !mobileEndHandle) return;
        
        // Переменные для хранения состояния перетаскивания
        let isDraggingStart = false;
        let isDraggingEnd = false;
        let trackRect = null;
        
        // Получаем элемент трека
        const rangeTrack = document.querySelector('.mobile-range-track');
        if (!rangeTrack) return;
        
        // Обновление положения ползунков
        function updateHandles() {
            if (!videoDuration) return;
            
            trackRect = rangeTrack.getBoundingClientRect();
            
            // Вычисляем позиции ручек в процентах
            const startPercent = (videoStartTime / videoDuration) * 100;
            const endPercent = (videoEndTime / videoDuration) * 100;
            
            // Устанавливаем положение ручек
            mobileStartHandle.style.left = startPercent + '%';
            mobileEndHandle.style.left = endPercent + '%';
            
            // Обновляем прогресс-бар
            updateProgressBar();
        }
        
        // Обработчики для начальной ручки
        mobileStartHandle.addEventListener('mousedown', function(e) {
            e.preventDefault();
            isDraggingStart = true;
            trackRect = rangeTrack.getBoundingClientRect();
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
        
        mobileStartHandle.addEventListener('touchstart', function(e) {
            isDraggingStart = true;
            trackRect = rangeTrack.getBoundingClientRect();
            document.addEventListener('touchmove', handleTouchMove, { passive: false });
            document.addEventListener('touchend', handleTouchEnd);
            e.preventDefault();
        }, { passive: false });
        
        // Обработчик для конечной ручки
        mobileEndHandle.addEventListener('mousedown', function(e) {
            e.preventDefault();
            isDraggingEnd = true;
            trackRect = rangeTrack.getBoundingClientRect();
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
        
        mobileEndHandle.addEventListener('touchstart', function(e) {
            isDraggingEnd = true;
            trackRect = rangeTrack.getBoundingClientRect();
            document.addEventListener('touchmove', handleTouchMove, { passive: false });
            document.addEventListener('touchend', handleTouchEnd);
            e.preventDefault();
        }, { passive: false });
        
        // Обработчики событий перемещения
        function handleMouseMove(e) {
            if (!isDraggingStart && !isDraggingEnd) return;
            updateDragPosition(e.clientX);
        }
        
        function handleTouchMove(e) {
            if (!isDraggingStart && !isDraggingEnd) return;
            if (e.touches.length > 0) {
                updateDragPosition(e.touches[0].clientX);
                e.preventDefault();
            }
        }
        
        // Обновление позиции при перетаскивании
        function updateDragPosition(clientX) {
            // Обновляем прямоугольник трека
            trackRect = rangeTrack.getBoundingClientRect();
            
            // Вычисляем относительную позицию в пределах трека (0 - 1)
            let relativePosition = (clientX - trackRect.left) / trackRect.width;
            // Ограничиваем позицию в пределах трека
            relativePosition = Math.max(0, Math.min(1, relativePosition));
            
            // Вычисляем время на видео
            const time = relativePosition * videoDuration;
            
            if (isDraggingStart) {
                // Обновляем начальное время с минимальным допустимым интервалом 0.5 секунд
                videoStartTime = Math.min(videoEndTime - 0.5, time);
            } else if (isDraggingEnd) {
                // Обновляем конечное время с максимальной длительностью 15 секунд
                const maxEndTime = Math.min(videoDuration, videoStartTime + 15);
                videoEndTime = Math.min(maxEndTime, Math.max(videoStartTime + 0.5, time));
            }
            
            // Обновляем ручки и прогресс
            updateHandles();
            
            // Обновляем время видео для просмотра, если оно не воспроизводится
            if (!isPlaying && videoPreview) {
                try {
                    videoPreview.currentTime = isDraggingStart ? videoStartTime : videoEndTime;
                } catch(e) {
                    console.error('Ошибка при установке currentTime:', e);
                }
            }
        }
        
        // Обработчики окончания перетаскивания
        function handleMouseUp() {
            isDraggingStart = false;
            isDraggingEnd = false;
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        }
        
        function handleTouchEnd() {
            isDraggingStart = false;
            isDraggingEnd = false;
            document.removeEventListener('touchmove', handleTouchMove);
            document.removeEventListener('touchend', handleTouchEnd);
        }
        
        // Инициализируем положение ручек
        updateHandles();
    } // Добавлена закрывающая скобка для функции setupMobileTrimControls
    
    // Сброс редактора
    function resetEditor() {
        // Сбрасываем все значения
        currentFile = null;
        fileType = null;
        originalVideo = null;
        
        // Возвращаем интерфейс в исходное состояние
        uploadSection.style.display = 'flex';
        uploadSection.style.zIndex = '10'; // Возвращаем высокий z-index для отображения
        imageEditorSection.style.display = 'none';
        videoEditorSection.style.display = 'none';
        actionButtons.style.display = 'none';
        processingIndicator.style.display = 'none';
        
        // Удаляем класс индикации выбора файла
        editorContainer.classList.remove('file-selected');
        
        // Очищаем поле файла
        mediaFile.value = '';
        
        // Сбрасываем трансформацию изображения
        currentScale = 1;
        currentTranslateX = 0;
        currentTranslateY = 0;
        currentRotation = 0;
        updateImageTransform();
        
        console.log('Редактор сброшен в исходное состояние');
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
            // Проверяем, что значения корректны
            if (isFinite(videoStartTime) && isFinite(videoEndTime)) {
                // Округляем значения до 2 знаков после запятой для точности
                const startTime = Math.max(0, Math.round(videoStartTime * 100) / 100);
                const endTime = Math.min(videoDuration || 15, Math.round(videoEndTime * 100) / 100);
                
                console.log(`Отправляем время обрезки видео: ${startTime} - ${endTime}`);
                formData.append('video_start', startTime.toString());
                formData.append('video_end', endTime.toString());
            } else {
                console.warn('Некорректные значения времени обрезки:', videoStartTime, videoEndTime);
                // Установим значения по умолчанию
                formData.append('video_start', '0');
                formData.append('video_end', '15');
            }
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
    
    // Инициализация редактора при загрузке страницы
    init();
});
</script>
<?php /**PATH C:\OSPanel\domains\tyty\resources\views/media/media-editor/scripts.blade.php ENDPATH**/ ?>