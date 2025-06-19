/**
 * Модуль для работы с редактором медиа-файлов
 */
(function() {
    'use strict';
    
    // Объект состояния редактора
    const mediaState = {
        currentFile: null,
        fileType: null,
        originalVideo: null,
        currentScale: 1,
        currentTranslateX: 0,
        currentTranslateY: 0,
        currentRotation: 0,
        videoStartTime: 0,
        videoEndTime: 15,
        videoDuration: 0,
        isPlaying: false,
        isDragging: false,
        lastTouchDistance: 0,
        templateId: null
    };
    
    // Модуль для логирования
    const logger = {
        log: function(msg, data) {
            console.log(`[MediaEditor] ${msg}`, data || '');
            if (window.mobileLogEnabled && window.mobileLog) {
                window.mobileLog(msg);
            }
        },
        warn: function(msg, data) {
            console.warn(`[MediaEditor] ${msg}`, data || '');
            if (window.mobileLogEnabled && window.mobileLog) {
                window.mobileLog(msg, 'warn');
            }
        },
        error: function(msg, data) {
            console.error(`[MediaEditor] ${msg}`, data || '');
            if (window.mobileLogEnabled && window.mobileLog) {
                window.mobileLog(msg, 'error');
            }
        }
    };
    
    // DOM элементы редактора
    let elements = {
        mediaFile: null,
        uploadBtn: null,
        uploadSection: null,
        imageEditorSection: null,
        videoEditorSection: null,
        actionButtons: null,
        saveBtn: null,
        processingIndicator: null,
        editorContainer: null,
        imagePreview: null,
        imageViewport: null,
        videoPreview: null,
        mobileProgressBar: null,
        mobileStartHandle: null,
        mobileEndHandle: null
    };
    
    /**
     * Модуль интерфейса редактора
     */
    const UI = {
        // Инициализация DOM элементов
        initElements: function() {
            elements.mediaFile = document.getElementById('mediaFile');
            elements.uploadBtn = document.getElementById('uploadBtn');
            elements.uploadSection = document.getElementById('uploadSection');
            elements.imageEditorSection = document.getElementById('imageEditorSection');
            elements.videoEditorSection = document.getElementById('videoEditorSection');
            elements.actionButtons = document.getElementById('actionButtons');
            elements.saveBtn = document.getElementById('saveBtn');
            elements.processingIndicator = document.getElementById('processingIndicator');
            elements.editorContainer = document.querySelector('.media-editor-container');
            elements.imagePreview = document.getElementById('imagePreview');
            elements.imageViewport = document.getElementById('imageViewport');
            elements.videoPreview = document.getElementById('videoPreview');
            elements.mobileProgressBar = document.getElementById('mobileProgressBar');
            elements.mobileStartHandle = document.getElementById('mobileStartHandle');
            elements.mobileEndHandle = document.getElementById('mobileEndHandle');
            
            // Получаем ID шаблона
            mediaState.templateId = document.getElementById('templateId')?.value;
            
            logger.log('DOM элементы инициализированы');
        },
        
        // Скрытие секции загрузки
        hideUploadSection: function() {
            elements.uploadSection.style.display = 'none';
            elements.uploadSection.style.zIndex = '-1';
            logger.log('Секция загрузки скрыта');
        },
        
        // Показать редактор изображений
        showImageEditor: function(fileUrl) {
            this.hideUploadSection();
            elements.imagePreview.src = fileUrl;
            elements.imageEditorSection.style.display = 'block';
            elements.videoEditorSection.style.display = 'none';
            
            mediaState.currentScale = 1;
            mediaState.currentTranslateX = 0;
            mediaState.currentTranslateY = 0;
            mediaState.currentRotation = 0;
            this.updateImageTransform();
            
            logger.log('Редактор изображений активирован');
        },
        
        // Показать редактор видео
        showVideoEditor: function(fileUrl) {
            this.hideUploadSection();
            mediaState.originalVideo = mediaState.currentFile;
            
            elements.videoPreview.src = fileUrl;
            elements.imageEditorSection.style.display = 'none';
            elements.videoEditorSection.style.display = 'block';
            
            mediaState.videoStartTime = 0;
            mediaState.videoEndTime = 15;
            mediaState.videoDuration = 0;
            
            logger.log('Редактор видео активирован');
            VideoEditor.init();
        },
        
        // Обновление трансформации изображения
        updateImageTransform: function() {
            elements.imagePreview.style.transform = `translate(${mediaState.currentTranslateX}px, ${mediaState.currentTranslateY}px) scale(${mediaState.currentScale}) rotate(${mediaState.currentRotation}deg)`;
        },
        
        // Показать индикатор обработки 
        showProcessingIndicator: function() {
            elements.actionButtons.style.display = 'none';
            elements.processingIndicator.style.display = 'flex';
        },
        
        // Скрыть индикатор обработки
        hideProcessingIndicator: function() {
            elements.processingIndicator.style.display = 'none';
        },
        
        // Сброс редактора в начальное состояние
        reset: function() {
            mediaState.currentFile = null;
            mediaState.fileType = null;
            mediaState.originalVideo = null;
            
            elements.uploadSection.style.display = 'flex';
            elements.uploadSection.style.zIndex = '10';
            elements.imageEditorSection.style.display = 'none';
            elements.videoEditorSection.style.display = 'none';
            elements.actionButtons.style.display = 'none';
            elements.processingIndicator.style.display = 'none';
            
            elements.editorContainer.classList.remove('file-selected');
            elements.mediaFile.value = '';
            
            mediaState.currentScale = 1;
            mediaState.currentTranslateX = 0;
            mediaState.currentTranslateY = 0;
            mediaState.currentRotation = 0;
            this.updateImageTransform();
            
            logger.log('Редактор сброшен в исходное состояние');
        },
        
        // Показать сообщение об ошибке
        showError: function(message) {
            logger.error(message);
            this.hideProcessingIndicator();
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger position-fixed bottom-0 start-0 end-0 m-3';
            errorDiv.innerHTML = `
                <h5 class="alert-heading">Ошибка при обработке файла</h5>
                <p>${message}</p>
                <button type="button" class="btn btn-primary btn-sm mt-2" id="tryAgainBtn">
                    <i class="bi bi-arrow-repeat me-1"></i> Повторить
                </button>
            `;
            
            document.body.appendChild(errorDiv);
            
            // Обработчик для кнопки "Повторить"
            document.getElementById('tryAgainBtn').addEventListener('click', () => {
                errorDiv.remove();
                elements.actionButtons.style.display = 'flex';
                logger.log('Повторная попытка обработки файла...');
            });
        }
    };
    
    /**
     * Модуль для работы с редактором изображений
     */
    const ImageEditor = {
        init: function() {
            if (!elements.imagePreview) {
                logger.error('Элемент предпросмотра изображения не найден');
                return;
            }
            
            // Добавляем обработчики событий для перетаскивания
            elements.imagePreview.addEventListener('pointerdown', this.startDrag);
            window.addEventListener('pointermove', this.drag);
            window.addEventListener('pointerup', this.endDrag);
            window.addEventListener('pointercancel', this.endDrag);
            
            // Добавляем обработчики для жестов масштабирования
            elements.imagePreview.addEventListener('touchstart', this.handleTouchStart, { passive: false });
            elements.imagePreview.addEventListener('touchmove', this.handleTouchMove, { passive: false });
            
            logger.log('Редактор изображений инициализирован');
        },
        
        startDrag: function(e) {
            if (e.pointerType === 'touch' && e.isPrimary === false) return;
            
            mediaState.isDragging = true;
            mediaState.startX = e.clientX;
            mediaState.startY = e.clientY;
            mediaState.startTranslateX = mediaState.currentTranslateX;
            mediaState.startTranslateY = mediaState.currentTranslateY;
            
            e.target.setPointerCapture(e.pointerId);
            e.preventDefault();
            
            logger.log('Начато перетаскивание изображения');
        },
        
        drag: function(e) {
            if (!mediaState.isDragging || (e.pointerType === 'touch' && !e.isPrimary)) return;
            
            const deltaX = e.clientX - mediaState.startX;
            const deltaY = e.clientY - mediaState.startY;
            
            mediaState.currentTranslateX = mediaState.startTranslateX + deltaX;
            mediaState.currentTranslateY = mediaState.startTranslateY + deltaY;
            
            UI.updateImageTransform();
        },
        
        endDrag: function(e) {
            if (e.pointerType === 'touch' && !e.isPrimary) return;
            
            if (mediaState.isDragging) {
                mediaState.isDragging = false;
                if (e.target.releasePointerCapture) {
                    e.target.releasePointerCapture(e.pointerId);
                }
                logger.log('Завершено перетаскивание изображения');
            }
        },
        
        handleTouchStart: function(e) {
            if (e.touches.length === 2) {
                const touch1 = e.touches[0];
                const touch2 = e.touches[1];
                mediaState.lastTouchDistance = ImageEditor.getTouchDistance(touch1, touch2);
                
                e.preventDefault();
                logger.log('Начат жест масштабирования');
            }
        },
        
        handleTouchMove: function(e) {
            if (e.touches.length === 2) {
                const touch1 = e.touches[0];
                const touch2 = e.touches[1];
                
                const currentDistance = ImageEditor.getTouchDistance(touch1, touch2);
                
                if (mediaState.lastTouchDistance > 0) {
                    const scaleFactor = currentDistance / mediaState.lastTouchDistance;
                    mediaState.currentScale *= scaleFactor;
                    mediaState.currentScale = Math.max(0.5, Math.min(mediaState.currentScale, 5));
                    logger.log(`Масштаб изображения: ${mediaState.currentScale.toFixed(2)}`);
                }
                
                mediaState.lastTouchDistance = currentDistance;
                
                UI.updateImageTransform();
                e.preventDefault();
            }
        },
        
        getTouchDistance: function(touch1, touch2) {
            const dx = touch1.clientX - touch2.clientX;
            const dy = touch1.clientY - touch2.clientY;
            return Math.sqrt(dx * dx + dy * dy);
        }
    };
    
    /**
     * Модуль для работы с редактором видео
     */
    const VideoEditor = {
        init: function() {
            if (!elements.videoPreview) {
                logger.error('Элемент предпросмотра видео не найден');
                return;
            }
            
            // Обработчик события загрузки метаданных видео
            elements.videoPreview.addEventListener('loadedmetadata', this.handleVideoLoaded);
            
            // События видеоплеера
            elements.videoPreview.addEventListener('timeupdate', this.handleTimeUpdate);
            
            logger.log('Видеоредактор инициализирован');
        },
        
        handleVideoLoaded: function() {
            mediaState.videoDuration = this.duration;
            
            if (isNaN(mediaState.videoDuration) || !isFinite(mediaState.videoDuration) || mediaState.videoDuration <= 0 || mediaState.videoDuration > 3600) {
                logger.warn(`Некорректная длительность видео: ${mediaState.videoDuration}`);
                mediaState.videoDuration = Math.max(15, Math.min(mediaState.videoDuration, 600));
            }
            
            logger.log(`Длительность видео: ${mediaState.videoDuration.toFixed(2)} секунд`);
            
            mediaState.videoStartTime = 0;
            mediaState.videoEndTime = Math.min(mediaState.videoDuration, 15);
            
            VideoEditor.setupTrimControls();
            VideoEditor.updateProgressBar();
        },
        
        handleTimeUpdate: function() {
            if (mediaState.isPlaying && (this.currentTime < mediaState.videoStartTime || this.currentTime >= mediaState.videoEndTime)) {
                this.pause();
                this.currentTime = mediaState.videoStartTime;
                mediaState.isPlaying = false;
                logger.log('Воспроизведение видео остановлено (выход за пределы выбранного диапазона)');
            }
        },
        
        updateProgressBar: function() {
            if (!elements.mobileProgressBar) {
                logger.error('Элемент прогресс-бара не найден');
                return;
            }
            
            const startPercent = (mediaState.videoStartTime / mediaState.videoDuration) * 100;
            const endPercent = (mediaState.videoEndTime / mediaState.videoDuration) * 100;
            
            elements.mobileProgressBar.style.left = startPercent + '%';
            elements.mobileProgressBar.style.width = (endPercent - startPercent) + '%';
        },
        
        setupTrimControls: function() {
            if (!elements.mobileStartHandle || !elements.mobileEndHandle) {
                logger.error('Элементы управления обрезкой видео не найдены');
                return;
            }
            
            const rangeTrack = document.querySelector('.mobile-range-track');
            if (!rangeTrack) {
                logger.error('Элемент трека для обрезки видео не найден');
                return;
            }
            
            // Обработчики для начальной ручки
            elements.mobileStartHandle.addEventListener('mousedown', function(e) {
                VideoEditor.startDragHandle(e, 'start');
            });
            
            elements.mobileStartHandle.addEventListener('touchstart', function(e) {
                VideoEditor.startDragHandleTouch(e, 'start');
            }, { passive: false });
            
            // Обработчик для конечной ручки
            elements.mobileEndHandle.addEventListener('mousedown', function(e) {
                VideoEditor.startDragHandle(e, 'end');
            });
            
            elements.mobileEndHandle.addEventListener('touchstart', function(e) {
                VideoEditor.startDragHandleTouch(e, 'end');
            }, { passive: false });
            
            VideoEditor.updateHandles();
            logger.log('Элементы управления обрезкой видео настроены');
        },
        
        updateHandles: function() {
            if (!mediaState.videoDuration) return;
            
            const rangeTrack = document.querySelector('.mobile-range-track');
            mediaState.trackRect = rangeTrack.getBoundingClientRect();
            
            const startPercent = (mediaState.videoStartTime / mediaState.videoDuration) * 100;
            const endPercent = (mediaState.videoEndTime / mediaState.videoDuration) * 100;
            
            elements.mobileStartHandle.style.left = startPercent + '%';
            elements.mobileEndHandle.style.left = endPercent + '%';
            
            VideoEditor.updateProgressBar();
        },
        
        startDragHandle: function(e, handleType) {
            e.preventDefault();
            
            mediaState.isDraggingStart = handleType === 'start';
            mediaState.isDraggingEnd = handleType === 'end';
            
            const rangeTrack = document.querySelector('.mobile-range-track');
            mediaState.trackRect = rangeTrack.getBoundingClientRect();
            
            document.addEventListener('mousemove', VideoEditor.handleMouseMove);
            document.addEventListener('mouseup', VideoEditor.handleMouseUp);
            
            logger.log('Начато перемещение ' + (handleType === 'start' ? 'начальной' : 'конечной') + ' точки обрезки');
        },
        
        startDragHandleTouch: function(e, handleType) {
            mediaState.isDraggingStart = handleType === 'start';
            mediaState.isDraggingEnd = handleType === 'end';
            
            const rangeTrack = document.querySelector('.mobile-range-track');
            mediaState.trackRect = rangeTrack.getBoundingClientRect();
            
            document.addEventListener('touchmove', VideoEditor.handleTouchMove, { passive: false });
            document.addEventListener('touchend', VideoEditor.handleTouchEnd);
            
            e.preventDefault();
            logger.log('Начато перемещение ' + (handleType === 'start' ? 'начальной' : 'конечной') + ' точки обрезки (тач)');
        },
        
        handleMouseMove: function(e) {
            if (!mediaState.isDraggingStart && !mediaState.isDraggingEnd) return;
            VideoEditor.updateDragPosition(e.clientX);
        },
        
        handleTouchMove: function(e) {
            if (!mediaState.isDraggingStart && !mediaState.isDraggingEnd) return;
            if (e.touches.length > 0) {
                VideoEditor.updateDragPosition(e.touches[0].clientX);
                e.preventDefault();
            }
        },
        
        updateDragPosition: function(clientX) {
            const rangeTrack = document.querySelector('.mobile-range-track');
            mediaState.trackRect = rangeTrack.getBoundingClientRect();
            
            let relativePosition = (clientX - mediaState.trackRect.left) / mediaState.trackRect.width;
            relativePosition = Math.max(0, Math.min(1, relativePosition));
            
            const time = relativePosition * mediaState.videoDuration;
            
            if (mediaState.isDraggingStart) {
                mediaState.videoStartTime = Math.min(mediaState.videoEndTime - 0.5, time);
                logger.log(`Новая начальная точка: ${mediaState.videoStartTime.toFixed(1)} сек`);
            } else if (mediaState.isDraggingEnd) {
                const maxEndTime = Math.min(mediaState.videoDuration, mediaState.videoStartTime + 15);
                mediaState.videoEndTime = Math.min(maxEndTime, Math.max(mediaState.videoStartTime + 0.5, time));
                logger.log(`Новая конечная точка: ${mediaState.videoEndTime.toFixed(1)} сек`);
            }
            
            VideoEditor.updateHandles();
            
            if (!mediaState.isPlaying && elements.videoPreview) {
                try {
                    elements.videoPreview.currentTime = mediaState.isDraggingStart ? mediaState.videoStartTime : mediaState.videoEndTime;
                } catch(e) {
                    logger.error('Ошибка при установке currentTime: ' + e.message);
                }
            }
        },
        
        handleMouseUp: function() {
            mediaState.isDraggingStart = false;
            mediaState.isDraggingEnd = false;
            document.removeEventListener('mousemove', VideoEditor.handleMouseMove);
            document.removeEventListener('mouseup', VideoEditor.handleMouseUp);
            logger.log('Перемещение точки обрезки завершено');
        },
        
        handleTouchEnd: function() {
            mediaState.isDraggingStart = false;
            mediaState.isDraggingEnd = false;
            document.removeEventListener('touchmove', VideoEditor.handleTouchMove);
            document.removeEventListener('touchend', VideoEditor.handleTouchEnd);
            logger.log('Перемещение точки обрезки завершено (тач)');
        }
    };
    
    /**
     * Модуль для работы с AJAX-запросами
     */
    const ApiService = {
        processMedia: function() {
            if (!mediaState.currentFile) {
                logger.error('Ошибка: Нет выбранного файла');
                return Promise.reject(new Error('Нет выбранного файла'));
            }
            
            UI.showProcessingIndicator();
            logger.log('Начата обработка файла...');
            
            const formData = new FormData();
            formData.append('media_file', mediaState.currentFile);
            
            if (mediaState.templateId) {
                formData.append('template_id', mediaState.templateId);
                logger.log(`Добавлен ID шаблона: ${mediaState.templateId}`);
            }
            
            if (mediaState.fileType === 'image') {
                const cropData = {
                    scale: mediaState.currentScale,
                    x: mediaState.currentTranslateX,
                    y: mediaState.currentTranslateY,
                    rotation: mediaState.currentRotation
                };
                formData.append('crop_data', JSON.stringify(cropData));
                logger.log(`Добавлены данные кадрирования: ${JSON.stringify(cropData)}`);
            }
            
            if (mediaState.fileType === 'video') {
                if (isFinite(mediaState.videoStartTime) && isFinite(mediaState.videoEndTime)) {
                    const startTime = Math.max(0, Math.round(mediaState.videoStartTime * 100) / 100);
                    const endTime = Math.min(mediaState.videoDuration || 15, Math.round(mediaState.videoEndTime * 100) / 100);
                    
                    formData.append('video_start', startTime.toString());
                    formData.append('video_end', endTime.toString());
                    logger.log(`Добавлено время обрезки видео: ${startTime} - ${endTime}`);
                } else {
                    logger.warn(`Некорректные значения времени обрезки: ${mediaState.videoStartTime} - ${mediaState.videoEndTime}`);
                    formData.append('video_start', '0');
                    formData.append('video_end', '15');
                }
            }
            
            // Получаем CSRF-токен из мета-тега
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            logger.log('Отправка запроса на сервер...');
            
            return fetch('/media/process', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    // Улучшенная обработка ошибок сервера
                    return response.text().then(text => {
                        try {
                            // Пытаемся распарсить как JSON
                            const data = JSON.parse(text);
                            throw new Error(data.error || `Ошибка сервера: ${response.status}`);
                        } catch (e) {
                            // Если не получается распарсить как JSON, возвращаем текст ошибки
                            if (e instanceof SyntaxError) {
                                throw new Error(`Ошибка сервера (${response.status}): Проверьте логи сервера`);
                            }
                            throw e;
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    logger.log('Файл успешно обработан');
                    return data;
                } else {
                    throw new Error(data.error || 'Неизвестная ошибка сервера');
                }
            });
        }
    };
    
    /**
     * Модуль для работы с файлами
     */
    const FileHandler = {
        setupEvents: function() {
            // Обработчики событий для кнопок
            elements.uploadBtn.addEventListener('click', () => {
                logger.log('Кнопка выбора файла нажата');
                elements.mediaFile.click();
            });
            
            elements.mediaFile.addEventListener('change', this.handleFileSelect);
            elements.saveBtn.addEventListener('click', MediaEditor.processMedia);
            
            // Добавляем поддержку drag-n-drop для загрузки файла
            this.setupDragAndDrop();
            
            logger.log('Обработчики файлов настроены');
        },
        
        handleFileSelect: function() {
            if (!elements.mediaFile.files || elements.mediaFile.files.length === 0) {
                logger.warn('Файл не выбран');
                return;
            }
            
            mediaState.currentFile = elements.mediaFile.files[0];
            const fileUrl = URL.createObjectURL(mediaState.currentFile);
            
            logger.log(`Файл выбран: ${mediaState.currentFile.name}, тип: ${mediaState.currentFile.type}, размер: ${Math.round(mediaState.currentFile.size/1024)}KB`);
            
            // Определяем тип файла
            if (mediaState.currentFile.type.startsWith('image/')) {
                mediaState.fileType = 'image';
                UI.hideUploadSection();
                UI.showImageEditor(fileUrl);
            } else if (mediaState.currentFile.type.startsWith('video/')) {
                mediaState.fileType = 'video';
                UI.hideUploadSection();
                UI.showVideoEditor(fileUrl);
            } else {
                logger.error(`Неподдерживаемый тип файла: ${mediaState.currentFile.type}`);
                alert('Неподдерживаемый тип файла. Пожалуйста, выберите изображение или видео.');
                UI.reset();
                return;
            }
            
            elements.actionButtons.style.display = 'flex';
            elements.editorContainer.classList.add('file-selected');
        },
        
        setupDragAndDrop: function() {
            const dropZone = elements.editorContainer;
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, this.preventDefaults, false);
            });
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, this.highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, this.unhighlight, false);
            });
            
            dropZone.addEventListener('drop', this.handleDrop, false);
            
            logger.log('Настроена поддержка drag-n-drop');
        },
        
        preventDefaults: function(e) {
            e.preventDefault();
            e.stopPropagation();
        },
        
        highlight: function() {
            elements.uploadSection.classList.add('highlight');
        },
        
        unhighlight: function() {
            elements.uploadSection.classList.remove('highlight');
        },
        
        handleDrop: function(e) {
            logger.log('Файл перетянут в область загрузки');
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                elements.mediaFile.files = files;
                FileHandler.handleFileSelect();
            }
        }
    };
    
    /**
     * Основной объект редактора
     */
    const MediaEditor = {
        init: function() {
            logger.log('Инициализация редактора медиа...');
            
            // Инициализация элементов DOM
            UI.initElements();
            
            // Проверяем наличие элементов интерфейса
            if (!elements.uploadBtn || !elements.mediaFile) {
                logger.error('Ошибка: Основные элементы не найдены');
                return;
            }
            
            // Настройка обработчиков файлов
            FileHandler.setupEvents();
            
            // Инициализация редактирования изображения
            ImageEditor.init();
            
            logger.log('Инициализация редактора медиа завершена');
        },
        
        processMedia: function() {
            ApiService.processMedia()
                .then(data => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else if (data.file_name) {
                        // Резервный вариант - перенаправление на категории шаблонов
                        // с передачей параметра имени файла
                        window.location.href = '/client/templates/categories?file=' + encodeURIComponent(data.file_name);
                    } else {
                        // Запасной вариант, если не указан URL перенаправления
                        window.location.href = '/client/templates/categories';
                    }
                })
                .catch(error => {
                    logger.error('Ошибка при отправке запроса: ' + error.message);
                    UI.showError(error.message || 'Неизвестная ошибка');
                });
        }
    };
    
    // Инициализация редактора при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        // Добавляем глобальную ссылку для доступа из других компонентов
        window.mediaEditor = MediaEditor;
        
        // Экспортируем функцию processMedia для вызова из других скриптов
        window.processMedia = MediaEditor.processMedia;
        
        // Запускаем инициализацию
        MediaEditor.init();
    });
})();
