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
    
    // ИСПРАВЛЕНИЕ: Определяем CSRF-токен в начале скрипта
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Проверяем доступность CSRF-токена
    if (!csrfToken) {
        console.error('CSRF-токен не найден. Добавьте meta[name="csrf-token"] в шаблон.');
    }
    
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
    
    // Инициализация редактора - исправляем дублирование событий
    function init() {
        // Удаляем все существующие обработчики событий с кнопки
        uploadBtn.removeEventListener('click', handleUploadButtonClick);
        
        // Добавляем один обработчик события
        uploadBtn.addEventListener('click', handleUploadButtonClick);
        
        // Удаляем старые события с input[file]
        mediaFile.removeEventListener('change', handleFileSelect);
        // Добавляем новый обработчик
        mediaFile.addEventListener('change', handleFileSelect);
        
        // Обработчик для кнопки сохранения
        saveBtn.removeEventListener('click', processMedia);
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
    
    // Функция-обработчик для кнопки загрузки - фиксим дублирование диалога
    function handleUploadButtonClick(e) {
        e.stopPropagation(); // Предотвращаем всплытие события
        mediaFile.click();
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
    
    // Настройка видео-редактора с мобильными элементами управления - ИСПРАВЛЕНО
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
            
            // Выводим информацию о длительности в консоль для отладки
            console.debug("Видео загружено. Длительность:", videoDuration, "Начало:", videoStartTime, "Конец:", videoEndTime);
        });
        
        // События видеоплеера
        videoPreview.addEventListener('timeupdate', function() {
            // Если видео проигрывается и вышло за пределы выбранного диапазона,
            // останавливаем воспроизведение или перематываем на начало выбранного фрагмента
            if (this.currentTime < videoStartTime) {
                this.currentTime = videoStartTime;
            } else if (this.currentTime >= videoEndTime) {
                if (isPlaying) {
                    this.pause();
                    isPlaying = false;
                    this.currentTime = videoStartTime;  // Перематываем на начало фрагмента
                }
            }
        });
        
        // Добавляем кнопку воспроизведения/паузы выбранного фрагмента
        if (!document.getElementById('videoPlayButton')) {
            const playButton = document.createElement('button');
            playButton.id = 'videoPlayButton';
            playButton.className = 'btn btn-primary btn-sm position-absolute';
            playButton.style.bottom = '60px';
            playButton.style.right = '20px';
            playButton.style.zIndex = '10';
            playButton.innerHTML = '<i class="bi bi-play-fill"></i> Воспроизвести';
            
            videoPreview.parentNode.appendChild(playButton);
            
            playButton.addEventListener('click', function() {
                if (isPlaying) {
                    videoPreview.pause();
                    isPlaying = false;
                    this.innerHTML = '<i class="bi bi-play-fill"></i> Воспроизвести';
                } else {
                    // Устанавливаем текущее время видео и воспроизводим
                    videoPreview.currentTime = videoStartTime;
                    videoPreview.play().then(() => {
                        isPlaying = true;
                        this.innerHTML = '<i class="bi bi-pause-fill"></i> Пауза';
                    }).catch(err => {
                        console.error('Ошибка воспроизведения:', err);
                    });
                }
            });
        }
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
        
        // Обновление положения ползунков - ИСПРАВЛЕНО
        function updateHandles() {
            if (!videoDuration || !isFinite(videoDuration) || videoDuration <= 0) {
                console.warn("Невозможно обновить положение ползунков: некорректная длительность видео");
                return;
            }
            
            trackRect = rangeTrack.getBoundingClientRect();
            
            // Проверяем и нормализуем значения времени
            if (!isFinite(videoStartTime) || videoStartTime < 0) videoStartTime = 0;
            if (!isFinite(videoEndTime) || videoEndTime <= videoStartTime) 
                videoEndTime = Math.min(videoStartTime + 15, videoDuration);
            
            // Гарантируем, что конечное время не превышает длительность
            videoEndTime = Math.min(videoEndTime, videoDuration);
            
            // Вычисляем позиции ручек в процентах
            const startPercent = (videoStartTime / videoDuration) * 100;
            const endPercent = (videoEndTime / videoDuration) * 100;
            
            console.log("Обновление положения ползунков:", 
                "Начало:", videoStartTime.toFixed(1), "с", `(${startPercent.toFixed(1)}%)`, 
                "Конец:", videoEndTime.toFixed(1), "с", `(${endPercent.toFixed(1)}%)`,
                "Длительность видео:", videoDuration.toFixed(1), "с");
            
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
        
        // Обновление позиции при перетаскивании - ИСПРАВЛЕНО
        function updateDragPosition(clientX) {
            // Обновляем прямоугольник трека
            trackRect = rangeTrack.getBoundingClientRect();
            
            // Вычисляем относительную позицию в пределах трека (0 - 1)
            let relativePosition = (clientX - trackRect.left) / trackRect.width;
            // Ограничиваем позицию в пределах трека
            relativePosition = Math.max(0, Math.min(1, relativePosition));
            
            // Вычисляем время на видео
            const time = relativePosition * videoDuration;
            
            // DEBUG: Выводим текущую позицию для отладки
            console.debug("Перемещение ползунка:", 
                "Позиция курсора:", clientX, 
                "Трек:", `${trackRect.left}-${trackRect.right}`, 
                "Относительная позиция:", relativePosition.toFixed(3), 
                "Время:", time.toFixed(2));
            
            // Устанавливаем новое время в зависимости от передвигаемой ручки
            if (isDraggingStart) {
                // Обновляем начальное время с минимальным допустимым интервалом 0.5 секунд
                const newStartTime = Math.min(videoEndTime - 0.5, time);
                if (newStartTime !== videoStartTime) {
                    videoStartTime = newStartTime;
                    console.log("Обновлено начальное время:", videoStartTime.toFixed(2));
                }
            } else if (isDraggingEnd) {
                // Обновляем конечное время с максимальной длительностью 15 секунд
                const maxEndTime = Math.min(videoDuration, videoStartTime + 15);
                const newEndTime = Math.min(maxEndTime, Math.max(videoStartTime + 0.5, time));
                if (newEndTime !== videoEndTime) {
                    videoEndTime = newEndTime;
                    console.log("Обновлено конечное время:", videoEndTime.toFixed(2));
                }
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
        
        // Добавляем индикацию выбранного интервала
        function updateTimeIndicator() {
            if (!videoDuration) return;
            
            // Создаем или обновляем элемент для отображения времени
            let timeIndicator = document.getElementById('timeRangeIndicator');
            if (!timeIndicator) {
                timeIndicator = document.createElement('div');
                timeIndicator.id = 'timeRangeIndicator';
                timeIndicator.style.position = 'absolute';
                timeIndicator.style.bottom = '62px';
                timeIndicator.style.left = '50%';
                timeIndicator.style.transform = 'translateX(-50%)';
                timeIndicator.style.background = 'rgba(0,0,0,0.7)';
                timeIndicator.style.color = 'white';
                timeIndicator.style.padding = '5px 10px';
                timeIndicator.style.borderRadius = '4px';
                timeIndicator.style.fontSize = '14px';
                timeIndicator.style.fontWeight = 'bold';
                timeIndicator.style.zIndex = '100';
                rangeTrack.parentNode.appendChild(timeIndicator);
            }
            
            // Рассчитываем и отображаем интервал
            const duration = videoEndTime - videoStartTime;
            timeIndicator.textContent = `${videoStartTime.toFixed(1)}с - ${videoEndTime.toFixed(1)}с (${duration.toFixed(1)}с)`;
        }
        
        // Обновляем индикацию при изменении и инициализации
        updateTimeIndicator();
        rangeTrack.addEventListener('mouseup', updateTimeIndicator);
        rangeTrack.addEventListener('touchend', updateTimeIndicator);
    }
    
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
        
        // ИСПРАВЛЕНО: Улучшенная обработка параметров обрезки для видео
        if (fileType === 'video') {
            // Выводим отладочную информацию
            console.log("Отправка параметров видео:", {
                videoDuration,
                videoStartTime,
                videoEndTime,
                выбранный_интервал: `${videoStartTime.toFixed(1)}с - ${videoEndTime.toFixed(1)}с`,
                длительность_интервала: (videoEndTime - videoStartTime).toFixed(1) + "с"
            });
            
            // Проверяем, что значения корректны и видео загружено
            if (isFinite(videoStartTime) && isFinite(videoEndTime) && isFinite(videoDuration) && videoDuration > 0) {
                // Гарантируем, что значения находятся в допустимых пределах
                const startTime = Math.max(0, Math.min(videoDuration - 0.5, videoStartTime));
                const endTime = Math.max(startTime + 0.5, Math.min(videoDuration, videoEndTime));
                
                // Добавляем параметры с фиксированной точностью (3 десятичных знака)
                formData.append('video_start', startTime.toFixed(3));
                formData.append('video_end', endTime.toFixed(3));
                
                // Добавляем дополнительный параметр с длительностью для отладки
                formData.append('video_clip_duration', (endTime - startTime).toFixed(3));
                
                console.log(`Отправка окончательных параметров обрезки: начало=${startTime.toFixed(3)}, конец=${endTime.toFixed(3)}, длит.=${(endTime - startTime).toFixed(3)}`);
                
                // Добавляем параметр качества
                if (currentFile.size > 10 * 1024 * 1024) {
                    formData.append('quality', 'small');
                    console.log('Установлено низкое качество для большого файла: ' + Math.round(currentFile.size / (1024 * 1024)) + 'MB');
                } else {
                    formData.append('quality', 'medium');
                }
            } else {
                console.warn('Некорректные значения времени обрезки или длительности видео:', {
                    videoStartTime,
                    videoEndTime,
                    videoDuration
                });
                
                // Значения по умолчанию (без обрезки)
                formData.append('video_start', '0');
                formData.append('video_end', Math.min(15, videoDuration || 15).toString());
            }
        }
        
        // Отображаем дополнительную информацию для пользователя
        const infoText = document.createElement('p');
        infoText.className = 'text-center mt-2';
        
        if (fileType === 'video') {
            if (isFinite(videoStartTime) && isFinite(videoEndTime)) {
                const clipDuration = videoEndTime - videoStartTime;
                infoText.textContent = `Обрабатываем видео: ${clipDuration.toFixed(1)}с (${videoStartTime.toFixed(1)}с - ${videoEndTime.toFixed(1)}с)`;
            } else {
                infoText.textContent = 'Обрабатываем видео. Это может занять некоторое время...';
            }
        } else {
            infoText.textContent = 'Обрабатываем изображение...';
        }
        
        document.querySelector('.spinner-border').parentNode.appendChild(infoText);
        
        // ИСПРАВЛЕНИЕ: Добавляем проверку CSRF токена перед отправкой
        if (!csrfToken) {
            // Показываем ошибку, если токен не найден
            processingIndicator.style.display = 'none';
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger position-fixed bottom-0 start-0 end-0 m-3';
            errorDiv.innerHTML = `
                <h5 class="alert-heading">Ошибка безопасности</h5>
                <p>CSRF-токен не найден. Обновите страницу или обратитесь к администратору.</p>
                <button type="button" class="btn btn-primary btn-sm mt-2" onclick="window.location.reload()">
                    <i class="bi bi-arrow-repeat me-1"></i> Обновить страницу
                </button>
            `;
            
            document.body.appendChild(errorDiv);
            return;
        }
        
        // ИСПРАВЛЕНИЕ: Добавляем таймаут, чтобы показать индикатор обработки перед отправкой запроса
        setTimeout(() => {
            // Отправляем запрос на сервер с добавленным try-catch для отлова сетевых ошибок
            try {
                fetch('{{ route("media.process") }}', {
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
                        
                        // Показываем сообщение об успехе
                        const successMessage = document.createElement('div');
                        successMessage.className = 'alert alert-success position-fixed top-0 start-0 end-0 m-3 text-center';
                        successMessage.textContent = 'Файл успешно обработан! Перенаправляем...';
                        document.body.appendChild(successMessage);
                        
                        // Добавляем небольшую задержку перед перенаправлением
                        setTimeout(() => {
                            // После успешной обработки перенаправляем пользователя
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                // Если нет URL для перенаправления, просто перезагружаем страницу
                                window.location.reload();
                            }
                        }, 1000);
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
                        <button type="button" class="btn btn-secondary btn-sm mt-2 ms-2" id="reloadPageBtn">
                            <i class="bi bi-arrow-clockwise me-1"></i> Обновить страницу
                        </button>
                    `;
                    
                    document.body.appendChild(errorDiv);
                    
                    // Обработчики для кнопок
                    document.getElementById('tryAgainBtn').addEventListener('click', () => {
                        errorDiv.remove();
                        actionButtons.style.display = 'flex';
                    });
                    
                    document.getElementById('reloadPageBtn').addEventListener('click', () => {
                        window.location.reload();
                    });
                });
            } catch (e) {
                console.error('Критическая ошибка при отправке запроса:', e);
                processingIndicator.style.display = 'none';
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger position-fixed bottom-0 start-0 end-0 m-3';
                errorDiv.innerHTML = `
                    <h5 class="alert-heading">Критическая ошибка</h5>
                    <p>${e.message || 'Произошла неизвестная ошибка при отправке данных'}</p>
                    <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Обновить страницу
                    </button>
                `;
                
                document.body.appendChild(errorDiv);
            }
        }, 500); // Небольшая задержка для отображения индикатора загрузки
    }
    
    // Инициализация редактора при загрузке страницы - блокируем повторные инициализации
    if (!window.editorInitialized) {
        init();
        window.editorInitialized = true;
        console.log('Редактор инициализирован в первый раз');
    } else {
        console.log('Редактор уже был инициализирован ранее');
    }
});
</script>
