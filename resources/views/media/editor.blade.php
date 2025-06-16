@extends('layouts.app')

@section('content')
<div class="">
    <div class="row justify-content-center">
        <div class="card shadow-sm">
            <div class="">
                <div class="media-editor-container">
                    @if(isset($template))
                        <input type="hidden" id="templateId" value="{{ $template->id }}">
                    @endif
                    
                    <!-- ИСПРАВЛЕНИЕ: Добавляем скрытый CSRF-токен -->
                    <meta name="csrf-token" content="{{ csrf_token() }}">
                    
                    <!-- Секция загрузки файла -->
                    @include('media.media-editor.upload-section')
                    
                    <!-- Редактор изображений -->
                    @include('media.media-editor.image-editor')
                    
                    <!-- Редактор видео -->
                    @include('media.media-editor.video-editor')
                    
                    <!-- Кнопки действий -->
                    @include('media.media-editor.action-buttons')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Индикатор обработки -->
@include('media.media-editor.processing-indicator')

<!-- Подключение стилей -->
@include('media.media-editor.styles')

<!-- Подключение скриптов -->
@include('media.media-editor.scripts')

@endsection

@section('styles')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- ИСПРАВЛЕНИЕ: Добавляем стиль для индикатора загрузки -->
    <style>
        /* Основные стили редактора */
        .fullscreen-editor {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1050;
            background: #fff;
            overflow: hidden;
        }
        
        /* Контейнеры медиа */
        .max-height-full {
            max-height: 80vh;
            object-fit: contain;
        }
        
        /* Стили для изображений */
        .image-container {
            position: relative;
            overflow: hidden;
            height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #000;
        }
        
        #imageViewport {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        #imagePreview {
            touch-action: none;
            transform-origin: center;
            max-width: 100%;
            max-height: 100%;
        }
        
        /* Стили для видео-контейнера */
        .video-container {
            background-color: #000;
            height: 90vh;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #videoPreview {
            max-height: 80%;
            max-width: 100%;
            object-fit: contain;
        }
        .action-buttons {
            max-width: 100%;
        }
        /* Стиль для таймлайна видео */
        .video-timeline-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 12px;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 5;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        
        /* Мобильный ползунок */
        .mobile-trim-slider {
            position: relative;
            height: 52px;
            margin: 12px 0;
        }
        
        .mobile-range-track {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 12px;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) inset;
        }
        
        .mobile-range-progress {
            position: absolute;
            height: 100%;
            background: rgba(13, 110, 253, 0.9);
            border-radius: 6px;
            top: 0;
            box-shadow: 0 0 8px rgba(13, 110, 253, 0.5);
            will-change: left, width;
        }
        
        /* Ручки для мобильного интерфейса */
        .mobile-handle {
            position: absolute;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            top: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            touch-action: none;
            border: 2px solid #0d6efd;
            -webkit-tap-highlight-color: transparent;
        }
        
        .handle-grip {
            width: 4px;
            height: 22px;
            background-color: #0d6efd;
            border-radius: 2px;
            position: relative;
        }
        
        .handle-grip::before,
        .handle-grip::after {
            content: "";
            position: absolute;
            width: 4px;
            height: 14px;
            background-color: #0d6efd;
            border-radius: 2px;
        }
        
        .handle-grip::before {
            left: -7px;
        }
        
        .handle-grip::after {
            right: -7px;
        }
        
        .mobile-handle.start-handle .handle-grip::before,
        .mobile-handle.end-handle .handle-grip::after {
            display: none;
        }
        
        .mobile-handle:active {
            transform: translate(-50%, -50%) scale(1.15);
            background-color: #e6f2ff;
            box-shadow: 0 0 16px rgba(13, 110, 253, 0.7);
        }
        
        /* Глобальные состояния перетаскивания */
        body.mobile-dragging {
            overflow: hidden !important;
            touch-action: none !important;
        }
        
        body.mobile-dragging * {
            user-select: none !important;
        }
        
        /* Кнопки действий редактора */
        .action-buttons {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px;
            background: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 20;
            border-top: 1px solid #eaeaea;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px;
        }
        
        /* Индикатор загрузки */
        .processing-indicator {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .processing-indicator p {
            margin-top: 15px;
            font-size: 16px;
            font-weight: 500;
        }
        
        /* Стиль для предотвращения дублирования кнопок */
        .btn.disabled {
            pointer-events: none;
            opacity: 0.65;
        }
        
        /* Медиа-запросы для адаптивности */
        @media (max-height: 700px) {
            .video-container, .image-container {
                height: 60vh;
            }
        }
        
        @media (max-height: 600px) {
            .video-container, .image-container {
                height: 55vh;
            }
        }
        
        @media (max-width: 576px) {
            .mobile-handle {
                width: 36px;
                height: 36px;
            }
            
            .handle-grip {
                height: 18px;
            }
            
            .action-buttons .btn {
                padding: 12px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 400px) {
            .video-timeline-container {
                padding: 12px 8px;
            }
            
            .mobile-trim-slider {
                height: 40px;
                margin: 8px 0;
            }
            
            .mobile-handle {
                width: 32px;
                height: 32px;
            }
            
            .handle-grip {
                height: 16px;
                width: 3px;
            }
        }
        
        @media (orientation: portrait) and (max-width: 576px) {
            .video-container, .image-container {
                height: 90vh;
            }
        }
    </style>
@endsection

@section('scripts')
    <!-- Встроенная минимальная реализация слайдера вместо внешних библиотек -->
    <script>
        // Создаем пространство имен для встроенного слайдера
        window.SimpleRangeSlider = {
            create: function(container, options) {
                if (!container) return null;
                
                // Очищаем контейнер
                container.innerHTML = '';
                container.classList.add('simple-slider-container');
                
                // Создаем основные элементы
                const track = document.createElement('div');
                track.classList.add('simple-slider-track');
                
                const range = document.createElement('div');
                range.classList.add('simple-slider-range');
                
                const startHandle = document.createElement('div');
                startHandle.classList.add('simple-slider-handle', 'start-handle');
                
                const endHandle = document.createElement('div');
                endHandle.classList.add('simple-slider-handle', 'end-handle');
                
                // Добавляем элементы в контейнер
                track.appendChild(range);
                container.appendChild(track);
                container.appendChild(startHandle);
                container.appendChild(endHandle);
                
                // Начальные значения
                let startValue = parseFloat(options.start[0]) || 0;
                let endValue = parseFloat(options.start[1]) || 15;
                const min = parseFloat(options.range.min) || 0;
                const max = parseFloat(options.range.max) || 15;
                const step = parseFloat(options.step) || 1;
                
                // Проверяем корректность начальных значений
                if (isNaN(startValue) || !isFinite(startValue)) startValue = min;
                if (isNaN(endValue) || !isFinite(endValue)) endValue = max;
                
                // Функция безопасного получения числа
                function safeNumber(value, defaultVal = 0) {
                    const num = parseFloat(value);
                    return isNaN(num) || !isFinite(num) ? defaultVal : num;
                }
                
                // Функция для обновления позиций
                function updatePositions() {
                    // Убеждаемся, что значения валидные
                    startValue = safeNumber(startValue, min);
                    endValue = safeNumber(endValue, max);
                    
                    const trackWidth = track.offsetWidth;
                    const startPos = Math.max(0, Math.min(100, ((startValue - min) / (max - min) * 100)));
                    const endPos = Math.max(0, Math.min(100, ((endValue - min) / (max - min) * 100)));
                    
                    startHandle.style.left = startPos + '%';
                    endHandle.style.left = endPos + '%';
                    range.style.left = startPos + '%';
                    range.style.width = (endPos - startPos) + '%';
                    
                    // Вызываем callback если задан
                    if (typeof options.onUpdate === 'function') {
                        try {
                            options.onUpdate([startValue, endValue]);
                        } catch (e) {
                            console.error('Ошибка в обработчике onUpdate:', e);
                        }
                    }
                }
                
                // Функция для установки значений
                function setValue(values, silent) {
                    if (!Array.isArray(values) || values.length !== 2) {
                        console.error('Неверный формат значений для слайдера:', values);
                        return;
                    }
                    
                    // Безопасно парсим значения
                    const newStart = safeNumber(values[0], startValue);
                    const newEnd = safeNumber(values[1], endValue);
                    
                    // Устанавливаем новые значения с проверками на корректность диапазона
                    startValue = Math.max(min, Math.min(newStart, newEnd - step));
                    endValue = Math.min(max, Math.max(newEnd, startValue + step));
                    
                    updatePositions();
                    
                    if (!silent && typeof options.onChange === 'function') {
                        try {
                            options.onChange([startValue, endValue]);
                        } catch (e) {
                            console.error('Ошибка в обработчике onChange:', e);
                        }
                    }
                }
                
                // Настраиваем перетаскивание ручек
                let dragging = null;
                let startX, startLeft;
                
                function startDrag(e, handle) {
                    e.preventDefault();
                    dragging = handle;
                    
                    // Запоминаем начальные координаты
                    startX = e.type.includes('touch') ? 
                        e.touches[0].clientX : 
                        e.clientX;
                    
                    startLeft = parseFloat(handle.style.left || '0');
                    
                    document.addEventListener('mousemove', drag);
                    document.addEventListener('touchmove', drag, { passive: false });
                    document.addEventListener('mouseup', stopDrag);
                    document.addEventListener('touchend', stopDrag);
                }
                
                function drag(e) {
                    if (!dragging) return;
                    e.preventDefault();
                    
                    const clientX = e.type.includes('touch') ? 
                        e.touches[0].clientX : 
                        e.clientX;
                    
                    const deltaX = clientX - startX;
                    const trackWidth = track.offsetWidth;
                    const percentDelta = deltaX / trackWidth * 100;
                    const newLeft = Math.max(0, Math.min(100, startLeft + percentDelta));
                    
                    const newValue = min + (newLeft / 100) * (max - min);
                    const roundedValue = Math.max(min, Math.min(max, Math.round(newValue / step) * step));
                    
                    try {
                        if (dragging === startHandle) {
                            setValue([roundedValue, endValue]);
                        } else if (dragging === endHandle) {
                            setValue([startValue, roundedValue]);
                        }
                    } catch (e) {
                        console.error('Ошибка при перетаскивании:', e);
                    }
                }
                
                function stopDrag() {
                    if (!dragging) return;
                    
                    document.removeEventListener('mousemove', drag);
                    document.removeEventListener('touchmove', drag);
                    document.removeEventListener('mouseup', stopDrag);
                    document.removeEventListener('touchend', stopDrag);
                    
                    dragging = null;
                }
                
                // Добавляем обработчики событий с учетом passive для touchstart
                startHandle.addEventListener('mousedown', e => startDrag(e, startHandle));
                startHandle.addEventListener('touchstart', e => startDrag(e, startHandle), { passive: false });
                endHandle.addEventListener('mousedown', e => startDrag(e, endHandle));
                endHandle.addEventListener('touchstart', e => startDrag(e, endHandle), { passive: false });
                
                // Инициализируем позиции
                updatePositions();
                
                // Возвращаем API для работы со слайдером
                return {
                    set: setValue,
                    get: function() {
                        return [startValue, endValue];
                    },
                    on: function(event, callback) {
                        if (event === 'update') {
                            options.onUpdate = callback;
                        } else if (event === 'change') {
                            options.onChange = callback;
                        }
                    },
                    destroy: function() {
                        container.innerHTML = '';
                        container.classList.remove('simple-slider-container');
                    },
                    container: container
                };
            }
        };
        
        // Делаем библиотеку доступной глобально
        window.noUiSlider = window.SimpleRangeSlider;
        window.noUiSliderLoaded = true;
        
        // Уведомляем о загрузке слайдера
        window.dispatchEvent(new Event('noUiSliderLoaded'));
    </script>
    @include('media.media-editor.scripts')
@endsection
